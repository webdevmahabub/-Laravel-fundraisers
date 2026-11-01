<?php

test('public home page loads successfully', function () {
    $this->get('/')->assertStatus(200);
});

test('campaign browse page loads successfully', function () {
    $this->get('/fundraisers')->assertStatus(200);
});
