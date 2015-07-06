<?php

namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use FireflyIII\Models\Account;
use Log;

/**
 * Class OpposingAccountIban
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class OpposingAccountIban extends BasicConverter implements ConverterInterface
{

    /**
     * If mapped, return account. Otherwise, only return the name itself.
     *
     * @return Account|string
     */
    public function convert()
    {
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);

            return $account;
        } else {
            if (strlen($this->value) > 0) {
                $set = Auth::user()->accounts()->get();
                /** @var Account $account */
                foreach ($set as $account) {
                    if ($account->iban == $this->value) {
                        Log::debug('OpposingAccountIban::convert found an Account (#' . $account->id . ': ' . $account->name . ') with IBAN ' . $this->value);

                        return $account;
                    }
                }
            }

            return $this->value;
        }
    }
}