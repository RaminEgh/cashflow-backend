<?php

namespace App\Services\Banking;

use App\Services\Banking\Adapters\MellatBankAdapter;
use App\Services\Banking\Adapters\ParsianBankAdapter;
use App\Services\Banking\Adapters\SamanBankAdapter;
use InvalidArgumentException;

class BankAdapterFactory
{
    public function make(string $bankIdentifier): BankAdapterInterface
    {
        return match (strtolower($bankIdentifier)) {
            'parsian' => new ParsianBankAdapter,
            'parsyan' => new ParsianBankAdapter,
            'mellat' => new MellatBankAdapter,
            'saman' => new SamanBankAdapter,
            default => throw new InvalidArgumentException("Unsupported bank: [{$bankIdentifier}]"),
        };
    }
}
