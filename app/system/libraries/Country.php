<?php
use System\Models\Countries_model;

/**
 * Country Class
 *
 * @package System
 */
class Country
{
    const ISO_CODE_2 = 2;
    const ISO_CODE_3 = 3;

    protected $defaultFormat;

    protected $requiredAddressKeys = [
        'address_1',
        'address_2',
        'city',
        'postcode',
        'state',
        'country',
    ];

    protected $countriesCollection = [];

    public function __construct()
    {
        $this->loadCountries();

        $this->setDefaultFormat("{address_1}\n{address_2}\n{city} {postcode}\n{state}\n{country}", [
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{state}',
            '{country}',
        ]);
    }

    public function addressFormat($address = [], $useLineBreaks = TRUE)
    {
        if (empty($address))
            return null;

        list($format, $placeholders) = $this->getDefaultFormat();

        // Override format if present in address array
        if (!empty($address['format']))
            $format = $address['format'];

        $formattedAddress = str_replace(["\r\n", "\r", "\n"], '<br />',
            preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '<br />',
                trim(str_replace($placeholders, $this->evalAddress($address), $format))
            )
        );

        if (!$useLineBreaks)
            $formattedAddress = str_replace('<br />', ', ', $formattedAddress);

        return $formattedAddress;
    }

    public function getCountryNameById($id = null)
    {
        if (!$countryModel = $this->countriesCollection->find($id))
            return null;

        return $countryModel->country_name;
    }

    public function getCountryCodeById($id = null, $codeType = null)
    {
        if (!$countryModel = $this->countriesCollection->where('location_id', $id)->first())
            return null;

        return (is_null($codeType) OR $codeType == static::ISO_CODE_2)
            ? $countryModel->iso_code_2 : $countryModel->iso_code_3;
    }

    public function getDefaultFormat()
    {
        return $this->defaultFormat;
    }

    public function setDefaultFormat($format, $placeholders = [])
    {
        $this->defaultFormat = [$format, $placeholders];
    }

    public function listAll($column = null, $key = 'country_id')
    {
        if (is_null($key))
            return $this->countriesCollection;

        return $this->countriesCollection->pluck($column, $key)->all();
    }

    protected function evalAddress($address)
    {
        if (isset($address['country_id']) AND !isset($address['country']))
            $address['country'] = $address['country_id'];

        $result = [];
        foreach ($this->requiredAddressKeys as $key) {
            $value = isset($address[$key]) ? $address[$key] : '';

            if ($key == 'country') {
                $value = $this->processCountryValue($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    protected function processCountryValue($country)
    {
        if (is_numeric($country)) {
            return $this->getCountryNameById($country);
        } else if (!is_string($country) AND isset($country['country_name'])) {
            return $country['country_name'];
        }

        return $country;
    }

    protected function loadCountries()
    {
        if (!count($this->countriesCollection))
            $this->countriesCollection = Countries_model::isEnabled()->get();

        return $this->countriesCollection;
    }
}