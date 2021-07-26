<?php

$factory->define(App\TransactionProduct::class, function (Faker\Generator $faker) {

    $product = App\OpenCartProducts::where('status',1)
        ->inRandomOrder()->first();
    $quantity = rand(2,10);
    $computed_cv = $quantity * $product->can_commission_value;
    $computed_pv = $quantity * $product->personal_volume;
    $total = $quantity * $product->price;

    return [
        'shoppingcart_product_id' => $product->product_id,
        'quantity' => $quantity,
        'computed_cv' => $computed_cv,
        'price' => $product->price,
        'total' => $total
    ];
});