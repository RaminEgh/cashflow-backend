<?php

namespace App\Services\Banking;

interface BankAdapterInterface
{

    public function setAccount(array $credentials): self;

    public function getBalance(): float;

}
