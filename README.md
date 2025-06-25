i have done te task and made change in factory 
orderFactory

 public function definition()
    {
        return [
            'subtotal' => $subtotal = round(rand(100, 999) / 3, 2),
            'commission_owed' => round($subtotal * 0.1, 2),
            'external_order_id' => $this->faker->uuid(), //this line was added 
        ];
    }


Honeslty I used chatGPT for help and I have not done this Kind of Task Before with unit testing.

the screenshot is in results folder in root 
