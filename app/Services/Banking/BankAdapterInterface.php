<?php

namespace App\Services\Banking;

interface BankAdapterInterface
{
    public function setAccount(array $credentials): self;

    /**
     * @return array{accountNumber: string, balance: int, todayDepositAmount: int, todayWithdrawAmount: int, currency: string}
     */
    public function getBalance(): array;
}
