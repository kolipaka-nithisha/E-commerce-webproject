<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

// Fetch the user's cart items
$user_id = $_SESSION['user_id'];
if(isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1; // Default quantity

    // Check if the product is already in the cart
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // Update the quantity if it already exists
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);
    } else {
        // Insert a new item into the cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
}
// Remove an item from the cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
}
//HAndle quantity update
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $user_id, $product_id]);
}
// Fetch cart items for the user
$stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0px;
            color: #333;
        }
        .container{
            width:90%;
            max-width: 1200px;
            margin: 40px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow:0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h2{
            text-align:center;
            font-size: 2em;
            margin-bottom: 20px;
        }
        .cart-item{
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            margin-bottom:15px;
            background-color: #fff;
            border-radius:8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-item img{
            max-width: 100px;
            border-radius: 8px;
            margin-right: 20px;
        }
        .item-details{
            flex-grow: 1;
        }
        .item-name{
            font-size: 1.2em;
            font-weight:bold;
            color: #343a40;
        }
        .item-price{
            color: #495057;
            font-size: 1.1em;
        }
        .item-actions{
            display: flex;
            align-items: center;
            justify-content:flex-end;
        }
        .item-actions button, .item-actions form button
        {
            background-color: #007bff;
            color:white;
            border:none;
            padding: 8px 12px;
            margin-left:10px;
            border-radius:5px;
            cursor:pointer;
            transition: background-color 0.3s ease;
        }
        .item-actions button:hover, .item-actions form button:hover{
            background-color: #0056b3;
        }
        .quantity{
            width: 60px;
            padding:5px;
            margin-right:10px;
            border:1px solid #ccc;
            border-radius:4px; 
        }
        .cart-actions a {
        background-color: #28a745;
        color: white;
        padding: 10px 16px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
        }
        .cart-actions a:hover {
          background-color: #218838;
        }
        .total-cost{
            font-size: 1.6em;
            font-weight: bold;
            color: #343a40;
            margin-top: 20px;
            text-align: right;
        }
        .empty-cart{
            text-align: center;
            font-size: 1.2em;
            color: #6c757d;
        }
        #a1 a{
            padding:20px;
            margin-left:20px;
            border:1px solid #ccc;
            border-radius: 5px;
            text-decoration:none;   
            color:rgb(22, 23, 23);
            background-color:rgb(27, 124, 220);
        }
        #a1 a:hover{
            background-color: #0056b3;
            color: white;
        }

    </style>
</head>
<body>
<div class="container">
    <h2>YOUR CART</h2>
    <?php 
    if (empty($cart_items)) 
     {
        echo '<p class="empty-cart">Your cart is empty.</p>';
        echo '<div id="a1"><a href="../index.php">Back to Shop</a></div>';
    } else {
           //fetch product details for each cart item
           $product_ids= array_column($cart_items, 'product_id');
           $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
           $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
           $stmt->execute($product_ids);
           $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
              
           foreach($products as $product) {
                $quantity=0;
                foreach ($cart_items as $cart_item) {
                    if ($cart_item['product_id'] == $product['id']) {
                        $quantity = $cart_item['quantity'];
                        break;
                    }
                }
                $total_cost += $product['price'] * $quantity;//add product price to total cost

                echo "<div class='cart-item'>
                      <img src='../images/{$product['image']}' alt='{$product['name']}'>
                        <div class='item-details'>
                            <div class='item-name'>{$product['name']} </div>
                            <div class='item-price'>\${$product['price']} x $quantity</div>
                        </div>
                        <div class='item-actions'>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='product_id' value='{$product['id']}'>
                                <input type='number' name='quantity' value='$quantity' class='quantity' min='1'>
                                <button type='submit' name='update_quantity'>Update Quantity</button>
                            </form>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='product_id' value='{$product['id']}'>
                                <button type='submit' name='remove_from_cart'>Remove</button>
                            </form>
                        </div>
                    </div>";
        }
    }
    ?>
    <?php if (!empty($cart_items)) : ?>
        <div class="total-cost">
            Total Cost: $<?= number_format($total_cost, 2); ?>
         </div>
        <div class="cart-actions">
            <a href="../index.php">back to shop</a>
            <a href="checkout.php">Proceed to Checkout</a>
        </div>   
    <?php endif; ?>
</div>  
</body>
</html>