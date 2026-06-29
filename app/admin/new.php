<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Weather Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body>
    <div x-data="weatherApp" class="container">
        <h1>Farmers Weather Dashboard</h1>
        
        <!-- Location Input -->
        <div class="location">
            <input type="text" x-model="location" placeholder="Enter city or ZIP (e.g., Kampala, UG)">
            <button @click="getLocation()">Use My Location</button>
            <button @click="fetchWeather()">Update</button>
        </div>
        
        <!-- Loading/Error -->
        <div x-show="loading">Loading weather...</div>
        <div x-show="error" class="alert error" x-text="error"></div>
        
        <!-- Current Weather -->
        <div x-show="current" class="current-weather">
            <h2>Current in <span x-text="location"></span></h2>
            <p>Temperature: <span x-text="current.temp + '°C'"></span></p>
            <p>Humidity: <span x-text="current.humidity + '%'"></span></p>
            <p>Wind: <span x-text="current.wind_speed + ' m/s'"></span></p>
            <p>Conditions: <span x-text="current.description"></span></p>
        </div>
        
        <!-- Forecast -->
        <div x-show="forecast.length" class="forecast">
            <h2>5-Day Forecast</h2>
            <div class="forecast-grid">
                <template x-for="day in forecast">
                    <div class="forecast-day">
                        <p x-text="day.date"></p>
                        <p>Temp: <span x-text="day.temp + '°C'"></span></p>
                        <p>Rain: <span x-text="day.rain + ' mm'"></span></p>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Decision Helper -->
        <div x-show="current" class="decisions">
            <h2>Farming Decisions</h2>
            <ul>
                <li x-text="getIrrigationAdvice()"></li>
                <li x-text="getPlantingAdvice()"></li>
                <li x-text="getPestAdvice()"></li>
            </ul>
        </div>
        
        <!-- Alerts Dashboard -->
        <div class="alerts">
            <h2>Alerts</h2>
            <template x-for="alert in alerts">
                <div class="alert warning" x-text="alert"></div>
            </template>
            <div x-show="!alerts.length">No active alerts.</div>
        </div>
    </div>

    <script>
        // Alpine.js data
        function weatherApp() {
            return {
                apiKey: '4a758dd1aed04dc3950175920231609', // Replace with your key
                location: 'Kampala, UG', // Default to user's location
                current: null,
                forecast: [],
                alerts: [],
                loading: false,
                error: null,
                
                async init() {
                    this.fetchWeather();
                    setInterval(() => this.fetchWeather(), 900000); // Poll every 15 mins
                },
                
                async getLocation() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            async (pos) => {
                                const { latitude, longitude } = pos.coords;
                                this.location = `${latitude},${longitude}`;
                                await this.fetchWeather();
                            },
                            (err) => this.error = 'Location access denied.'
                        );
                    } else {
                        this.error = 'Geolocation not supported.';
                    }
                },
                
                async fetchWeather() {
                    this.loading = true;
                    this.error = null;
                    try {
                        const url = `https://api.openweathermap.org/data/3.0/onecall?lat=${this.location.split(',')[0] || ''}&lon=${this.location.split(',')[1] || ''}&q=${this.location}&exclude=minutely,hourly&units=metric&appid=${this.apiKey}`;
                        const res = await fetch(url);
                        if (!res.ok) throw new Error('API error');
                        const data = await res.json();
                        
                        this.current = {
                            temp: data.current.temp,
                            humidity: data.current.humidity,
                            wind_speed: data.current.wind_speed,
                            description: data.current.weather[0].description
                        };
                        
                        this.forecast = data.daily.slice(1, 6).map(day => ({
                            date: new Date(day.dt * 1000).toDateString(),
                            temp: day.temp.day,
                            rain: day.rain || 0
                        }));
                        
                        this.updateAlerts();
                        
                        // Cache for reliability
                        localStorage.setItem('weatherData', JSON.stringify({ current: this.current, forecast: this.forecast }));
                    } 
                    catch (e) {
                        this.error = 'Failed to fetch weather. Using cached data.';
                        const cached = localStorage.getItem('weatherData');
                        if (cached) {
                            const { current, forecast } = JSON.parse(cached);
                            this.current = current;
                            this.forecast = forecast;
                            this.updateAlerts();
                        }
                    }
                    this.loading = false;
                },
                
                updateAlerts() {
                    this.alerts = [];
                    if (this.current.temp < 10) this.alerts.push('Frost alert: Protect crops!');
                    if (this.forecast.some(day => day.rain > 10)) this.alerts.push('Heavy rain incoming: Check drainage.');
                    if (this.current.wind_speed > 15) this.alerts.push('High winds: Secure equipment.');
                    // Add more farmer-specific thresholds
                },
                
                getIrrigationAdvice() {
                    return this.current.humidity > 70 ? 'Soil moist – Skip irrigation today.' : 'Dry conditions – Irrigate soon.';
                },
                
                getPlantingAdvice() {
                    return this.current.temp > 20 && this.forecast[0].rain < 5 ? 'Ideal for planting.' : 'Wait for better weather.';
                },
                
                getPestAdvice() {
                    return this.current.humidity > 80 ? 'High humidity: Watch for fungal pests.' : 'Low pest risk.';
                }
            }
        }
    </script>
</body>
</html>