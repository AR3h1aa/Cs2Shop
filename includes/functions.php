<?php
// includes/functions.php
function get_user_avatar($conn, $user_id) {
    $query = "SELECT profile_pic FROM users WHERE id = " . (int)$user_id;
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['profile_pic'])) {
            return '/cs-hub/assets/profiles/' . $row['profile_pic'];
        }
    }
    return '/cs-hub/assets/profiles/default.png'; 
}



function product_display_name($product) {
    if (!empty($product['weapon'])) {
        return htmlspecialchars($product['weapon'] . ' | ' . $product['skin_name']);
    }
    return htmlspecialchars($product['name']);
}
?>