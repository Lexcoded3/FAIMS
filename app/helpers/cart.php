<?php
// app/helpers/cart.php

function get_or_create_cart($conn, $buyer_id) {
    $sql = "SELECT id FROM carts WHERE buyer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }
    
    // Create new cart
    $sql = "INSERT INTO carts (buyer_id) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    return $conn->insert_id;
}

function add_to_cart($conn, $buyer_id, $product_id, $quantity = 1) {
    $cart_id = get_or_create_cart($conn, $buyer_id);
    
    // Get current price
    $sql = "SELECT price FROM products WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) return false;
    
    $price = $product['price'];
    
    // Check if already in cart
    $sql = "SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cart_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Update quantity
        $new_qty = $row['quantity'] + $quantity;
        $sql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_qty, $row['id']);
        $stmt->execute();
    } else {
        // Insert new
        $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, price_at_add) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iidd", $cart_id, $product_id, $quantity, $price);
        $stmt->execute();
    }
    
    $stmt->close();
    return true;
}
?>