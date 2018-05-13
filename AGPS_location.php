<?php
namespace Shinovent\Tallennin;

/**
 * Class AGPS_location.
 * Used to read in OpenCellID Cellphone tower data to APCu cache,
 * and to fetch Cell Tower location from cache.
 * Data can be filtered by country (244 = Finland ...)
 * or by mobile phone operator (12 = DNA/Finnet, 21 = Elisa ...),
 * or by standard (GSM, LTE, UMTS ...)
 *
 * Enable APCu in php.ini with apc.enable_cli=On
 *
 * https://opencellid.org
 * http://www.mcc-mnc.com/
 *
 * @package Shinovent\Tallennin
 */
class AGPS_location
{
    const STANDARD_KEY = 0; //
    const MCC_KEY = 1; // Country code
    const MNC_KEY = 2; // Operator
    const LAC_KEY = 3;
    const CID_KEY = 4;
    const LATITUDE_KEY = 6;
    const LONGITUDE_KEY = 7;
    const CSV_DELIMITER = ',';

    /**
     * @var string CSV filename
     */
    private $csvFile;

    /**
     * @var array|null
     */
    private $countryFilter;

    /**
     * @var array|null
     */
    private $operatorFilter;

    /**
     * @var array|null
     */
    private $standardFilter;

    /**
     * AGPS_location constructor.
     * Stored data can be filtered
     * @param string $csvFile file path
     * @param array $filterByCountry Country codes to filter by or null
     * @param array $filterByOperator Operator code to filter by or null
     * @param array $filterByMobilePhoneStandard Standards to filter by or null
     */
    public function __construct(
        $csvFile,
        $filterByCountry = array(),
        $filterByOperator = array(),
        $filterByMobilePhoneStandard = array()
    ) {
        $this->csvFile = $csvFile;
        $this->countryFilter = $filterByCountry;
        $this->operatorFilter = $filterByOperator;
        $this->standardFilter = $filterByMobilePhoneStandard;
    }

    /**
     * Parse CSV to APCu cache.
     * @throws \Exception
     */
    public function createCellTowerTable()
    {
        $handle = fopen($this->csvFile, 'r');
        while (($row = fgetcsv($handle, 0, self::CSV_DELIMITER)) !== false) {
            // Filter by mobile phone standard
            if (
                !empty($this->standardFilter) &&
                in_array($row[self::STANDARD_KEY], $this->standardFilter)
            ) {
                continue;
            }

            // Filter by country code
            if (
                !empty($this->countryFilter) &&
                in_array($row[self::MCC_KEY], $this->countryFilter)
            ) {
                continue;
            }

            // Filter by operator
            if (
                !empty($this->operatorFilter) &&
                in_array($row[self::MNC_KEY], $this->operatorFilter)
            ) {
                continue;
            }
            echo "\nSTORE: " . $this->towerToString($row);
            $success = apcu_store($this->towerToString($row), array(
                'lat' => $row[self::LATITUDE_KEY],
                'lon' => $row[self::LONGITUDE_KEY]
            ));

            if ($success === false) {
                throw new \Exception(
                    "Out of memory or APCu cache not working!"
                );
            }
        }
    }

    /**
     * Combine tower data to string, to use as a key in APCu cache.
     * Standard is not used in key.
     *
     * @param array $row CSV row in array
     * @return string
     */
    public function towerToString($row)
    {
        return implode('.', array(
            $row[self::MCC_KEY],
            $row[self::MNC_KEY],
            $row[self::LAC_KEY],
            $row[self::CID_KEY]
        ));
    }

    /**
     * Get location of Cell Tower from APCu cache.
     * @param $MCC
     * @param $MNC
     * @param $LAC
     * @param $CID
     * @return array|false
     */
    public function getCellTowerLocation($MCC, $MNC, $LAC, $CID)
    {
        $key = $MCC . '.' . $MNC . '.' . $LAC . '.' . $CID;
        $hit = false;
        return apcu_fetch($key, $hit);
    }

    public function keyExistsInCache($key)
    {
        return apcu_exists($key);
    }
}
