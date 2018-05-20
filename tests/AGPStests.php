<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Shinovent\Tallennin as Shin;

require_once __DIR__ . "/../AGPS_location.php";

final class AGPStests extends TestCase
{
    /**
     * @var Shin\AGPS_location
     */
    private $agps;
    private $csvfile;

    public function __construct(
        ?string $name = null,
        array $data = [],
        string $dataName = ''
    ) {
        parent::__construct($name, $data, $dataName);
        $this->csvfile = __DIR__ . '/244_GSM.csv';
        $this->agps = new Shin\AGPS_location($this->csvfile);
    }

    public function testCsvFileIsReadable()
    {
        $this->assertIsReadable($this->csvfile);
    }

    public function testTowerStringify(): void
    {
        $row = array(
            0 => 'GSM',
            1 => '244',
            2 => '91',
            3 => '4111',
            4 => '11921',
            5 => '0',
            6 => '24.056625',
            7 => '61.197968',
            8 => '1000',
            9 => '2',
            10 => '1',
            11 => '1459815393',
            12 => '1471847554',
            13 => '0'
        );

        $this->assertEquals(
            '244.91.4111.11921',
            $this->agps->towerToString($row)
        );
    }

    public function testCsvIsReadIntoApcu(): void
    {
        // line 23: GSM,244,5,2040,62439,0,22.169596,60.482671,1990,11,1,1320924774,1478522873,0
        $this->agps->createCellTowerTable();
        $this->assertEquals(
            true,
            $this->agps->keyExistsInCache('244.5.2040.62439')
        );
    }

    public function testGetTowerLocationReturnsLocation()
    {
        $location = $this->agps->getCellTowerLocation(
            '244',
            '5',
            '2040',
            '62439'
        );
        $this->assertEquals('22.169596', $location['lat']);
        $this->assertEquals('60.482671', $location['lon']);
    }

    public function testFilteredParsing()
    {
        apcu_clear_cache();
        $this->agps = new Shin\AGPS_location($this->csvfile, array('244'), array('91'), array('GSM'));
        $this->agps->createCellTowerTable();

        $location = $this->agps->getCellTowerLocation(
            '244',
            '91',
            '4111',
            '11921'
        );
        $this->assertEquals('24.056625', $location['lat']);
        $this->assertEquals('61.197968', $location['lon']);
    }

    public function testFilteredOutTowerIsNotInCache()
    {
        $location = $this->agps->getCellTowerLocation(
            '244',
            '5',
            '2040',
            '62439'
        );

        $this->assertEquals(false, $location);
    }
}
