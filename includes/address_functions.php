<?php
// includes/address_functions.php

function get_user_addresses($pdo, $user_id) {
    $sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_address_by_id($pdo, $address_id) {
    $sql = "SELECT * FROM user_addresses WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$address_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function add_user_address($pdo, $user_id, $data) {
    $sql = "INSERT INTO user_addresses (user_id, address_line_1, address_line_2, city, state, postal_code, country, latitude, longitude, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $params = [
        $user_id,
        $data['address_line_1'],
        $data['address_line_2'],
        $data['city'],
        $data['state'],
        $data['postal_code'],
        $data['country'],
        $data['latitude'],
        $data['longitude'],
        $data['is_default']
    ];

    if ($stmt->execute($params)) {
        $new_id = $pdo->lastInsertId();
        if ($data['is_default']) {
            set_default_address($pdo, $user_id, $new_id);
        }
        return $new_id;
    }
    return false;
}

function update_user_address($pdo, $address_id, $user_id, $data) {
    $sql = "UPDATE user_addresses SET address_line_1 = ?, address_line_2 = ?, city = ?, state = ?, postal_code = ?, country = ?, latitude = ?, longitude = ?, is_default = ? WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $params = [
        $data['address_line_1'],
        $data['address_line_2'],
        $data['city'],
        $data['state'],
        $data['postal_code'],
        $data['country'],
        $data['latitude'],
        $data['longitude'],
        $data['is_default'],
        $address_id,
        $user_id
    ];

    if ($stmt->execute($params)) {
        if ($data['is_default']) {
            set_default_address($pdo, $user_id, $address_id);
        }
        return true;
    }
    return false;
}

function delete_user_address($pdo, $address_id, $user_id) {
    $sql = "DELETE FROM user_addresses WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$address_id, $user_id]);
}

function set_default_address($pdo, $user_id, $address_id) {
    try {
        $pdo->beginTransaction();

        $sql_unset = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?";
        $stmt_unset = $pdo->prepare($sql_unset);
        $stmt_unset->execute([$user_id, $address_id]);

        $sql_set = "UPDATE user_addresses SET is_default = 1 WHERE user_id = ? AND id = ?";
        $stmt_set = $pdo->prepare($sql_set);
        $stmt_set->execute([$user_id, $address_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}
?>
