
// Optional: show loading immediately
document.getElementById('weather-loading').style.display = 'block';

async function loadWeather() {
    try {
        const response = await fetch('get_weather.php');
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();

        if (data.error) {
            document.getElementById('weather-error').textContent = data.error;
            document.getElementById('weather-error').classList.remove('hidden');
            document.getElementById('weather-loading').style.display = 'none';
            return;
        }

        // Hide loading
        document.getElementById('weather-loading').style.display = 'none';

        // Main card - big temperature
        document.getElementById('temperature').textContent = data.temp;
        document.querySelector('.temp-unit').textContent = 'C';

        // Description & icon
        document.getElementById('weather-description').textContent = data.description;
        document.getElementById('weather-icon').src = `https://openweathermap.org/img/wn/${data.icon}@2x.png`;

        // Location badge
        document.getElementById('location-name').textContent = data.location;

        // Detail cards
        document.getElementById('feels-like').textContent = `${data.feels_like}°C`;
        document.getElementById('humidity').textContent = `${data.humidity}%`;
        document.getElementById('wind-speed').textContent = `${data.wind_speed} km/h`;
        document.getElementById('uv-index').textContent = data.uv;

        // Optional: if you want to show rain chance somewhere
        // Example: add a small badge or new card
        // document.getElementById('rain-chance').textContent = `${data.rain_chance}% today`;

    } catch (error) {
        console.error('Weather fetch error:', error);
        document.getElementById('weather-error').textContent = 'Could not load weather data right now';
        document.getElementById('weather-error').classList.remove('hidden');
        document.getElementById('weather-loading').style.display = 'none';
    }
}

// Run when page is ready
document.addEventListener('DOMContentLoaded', function() {
    loadWeather();

    // Optional: auto-refresh every 20 minutes
    // setInterval(loadWeather, 20 * 60 * 1000);
});
let isCelsius = true;

function toggleUnit() {
    isCelsius = !isCelsius;
    const temp = data.temp;
    const feels = data.feels_like;
    
    document.getElementById('temperature').textContent = isCelsius 
        ? temp 
        : Math.round(temp * 9/5 + 32);
    
    document.querySelector('.temp-unit').textContent = isCelsius ? 'C' : 'F';
    
    document.getElementById('feels-like').textContent = `${isCelsius 
        ? feels 
        : Math.round(feels * 9/5 + 32)}°${isCelsius ? 'C' : 'F'}`;
}

// Attach to your buttons (adjust selectors if needed)
document.querySelector('.btn.bg-success').addEventListener('click', () => {
    if (!isCelsius) toggleUnit();
});
document.querySelector('.btn.bg-error').addEventListener('click', () => {
    if (isCelsius) toggleUnit();
});