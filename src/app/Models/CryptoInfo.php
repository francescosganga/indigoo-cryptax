<?php

namespace CrypTax\Models;

use CrypTax\Models\Transaction;
use CrypTax\Utils\DateUtils;

class CryptoInfo
{
    /**
     * Cryptocurrency ticker.
     *
     * @var string
     */
    private $ticker;

    /**
     * Cryptocurrency name.
     *
     * @var string
     */
    private $name;

    /**
     * Current cryptocurrency balance.
     *
     * @var float
     */
    private $balance = 0.0;

    /**
     * Cryptocurrency balance on 01/01 of the selected fiscal year.
     *
     * @var float
     */
    private $balanceStartOfYear = 0.0;

    /**
     * Day of the fiscal year up to where the processing was done, 0-365.
     *
     * @var integer
     */
    private $currentDayOfYear = 0;

    /**
     * Balance of each day in the fiscal year.
     *
     * @var integer[]
     */
    private $dailyBalances = [];

    /**
     * Current report fiscal year.
     *
     * @var integer
     */
    private $fiscalYear;

    /**
     * Current value.
     *
     * @var float
     */
    private $value;

    /**
     * Initialize the cryptocurrency name and ticker and set the fiscal year.
     *
     * @param string $ticker
     * @param integer $fiscalYear
     * @param float $value
     */
    public function __construct($ticker, $fiscalYear, $value) {
        $this->ticker = $ticker;
        $this->name = $ticker;
        $this->fiscalYear = $fiscalYear;
        $this->value = $value;
    }

    /**
     * Save the balance at the beginning of the fiscal year.
     *
     * @return void
     */
    public function saveBalanceStartOfYear() {
        $this->balanceStartOfYear = $this->balance;
    }

    /**
     * Set the daily balances to the current ones until reach the specific day of year.
     *
     * @param  integer $day day of the year, 0-365
     * @return void
     */
    public function setBalancesUntilDay($day) {
        while ($this->currentDayOfYear < $day) {
            $this->dailyBalances[$this->currentDayOfYear] = $this->balance;
            $this->currentDayOfYear++;
        }
    }

    /**
     * Increment the cryptocurrency balance.
     *
     * @param float $amount
     * @param Transaction $transaction
     * @return void
     */
    public function incrementBalance($amount, $transaction = null) {
        $this->balance += $amount;

        if ($this->balance < 0) {
            throw new NegativeBalanceException($this->ticker, $this->balance, $transaction ? $transaction->date : null);
        }
    }

    /**
     * Get the cryptocurrency price at the day 01/01 of the selected fiscal year.
     *
     * @return float
     */
    public function getPriceStartOfYear() {
        if (array_sum($this->dailyBalances) === 0.0) {
            return 0.0;
        }

        return $this->value;
    }

    /**
     * Get the cryptocurrency price at the day 31/12 of the selected fiscal year.
     *
     * @return float
     */
    public function getPriceEndOfYear() {
        $this->setBalancesUntilDay(DateUtils::old_getNumerOfDaysInYear($this->fiscalYear) + 1);

        if (array_sum($this->dailyBalances) === 0.0) {
            return 0.0;
        }

        return $this->value;
    }

    /**
     * Get the cryptocurrency EUR value at the day 01/01 of the selected fiscal year.
     *
     * @return float
     */
    public function getValueStartOfYear() {
        return $this->getPriceStartOfYear() * $this->balanceStartOfYear;
    }

    /**
     * Get the cryptocurrency EUR value at the day 31/12 of the selected fiscal year.
     *
     * @return float
     */
    public function getValueEndOfYear() {
        return $this->getPriceEndOfYear() * $this->balance;
    }

    /**
     * Get the average EUR value (giacenza media) in the selected fiscal year.
     *
     * @param  string $priceDate the specified day price is used
     * @return float
     */
    public function getAverageValue($priceDate = '12-31') {
        $dailyBalancesSum = array_sum($this->dailyBalances);

        if ($dailyBalancesSum === 0.0) {
            return 0.0;
        }

        $daysInYear = DateUtils::getNumberOfDaysInYear($this->fiscalYear);
        $price = $this->value;

        return $dailyBalancesSum / $daysInYear * $price;
    }

    /**
     * Get the maximum EUR value in the selected fiscal year.
     *
     * @param  string $priceDate the specified day price is used
     * @return [type]            [description]
     */
    public function getMaxValue($priceDate = '12-31') {
        if (array_sum($this->dailyBalances) === 0.0) {
            return 0.0;
        }
        
        $price = $this->value;

        return max($this->dailyBalances) * $price;
    }

    /**
     * Get the daily values using the price at the beginning of the fiscal year.
     *
     * @return float[]
     */
    public function getDailyValuesStartOfYear() {
        return $this->getDailyValues($this->fiscalYear . '-01-01');
    }

    /**
     * Get the daily values using the price at the end of the fiscal year.
     *
     * @return float[]
     */
    public function getDailyValuesEndOfYear() {
        return $this->getDailyValues($this->fiscalYear . '-12-31');
    }

    /**
     * Get the daily values using the price at the specified date.
     * If priceDate is null, real daily prices are used.
     *
     * @param string $priceDate
     * @return float[]
     */
    public function getDailyValues($priceDate = null) {
        if (array_sum($this->dailyBalances) === 0.0) {
            return $this->dailyBalances;
        }

        return array_map(function ($balance, $day) use ($priceDate) {
            if ($priceDate === null) {
                $dateToFetch = DateUtils::getDateFromDayOfYear($day, $this->fiscalYear);
            } else {
                $dateToFetch = $priceDate;
            }

            $price = $this->value;

            return $balance * $price;
        }, $this->dailyBalances, array_keys($this->dailyBalances));
    }

    /**
     * Get the info used for the report rendering.
     *
     * @return array
     */
    public function getInfoForRender() {
        $info = [
            'name' => $this->name,
            'ticker' => $this->ticker,
            'price_start_of_year' => $this->getPriceStartOfYear(),
            'price_end_of_year' => $this->getPriceEndOfYear(),
            'balance_start_of_year' => $this->balanceStartOfYear,
            'balance_end_of_year' => $this->balance,
            'value_start_of_year' => $this->getValueStartOfYear(),
            'value_end_of_year' => $this->getValueEndOfYear(),
            'average_value' => $this->getAverageValue(),
            'max_value' => $this->getMaxValue()
        ];

        $info['balance_start_of_year'] = $this->formatBalance($info['balance_start_of_year'], $info['price_start_of_year']);
        $info['balance_end_of_year'] = $this->formatBalance($info['balance_end_of_year'], $info['price_end_of_year']);

        foreach (['price_start_of_year', 'price_end_of_year'] AS $field) {
            $info[$field] = number_format($info[$field], 3, ',', '.');
        }

        foreach (['value_start_of_year', 'value_end_of_year', 'average_value', 'max_value'] AS $field) {
            $info[$field] = number_format($info[$field], 2, ',', '.');
        }

        return $info;
    }

    /**
     * Format the balance based on the price.
     *
     * @param float $balance
     * @param float $price
     * @return string
     */
    private function formatBalance($balance, $price) {
        $digits = 2;

        if ($price > 10 && $balance > 0) {
            $digits = 8;
        }

        return number_format($balance, $digits, ',', '.');
    }

}
