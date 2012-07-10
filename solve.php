<?php

define('DEFAULT_INPUT_FILE_NAME', "cities.txt");

class City {

    public $name;
    public $latitude;
    public $longitude;

    public function __construct($name, $latitude, $longitude) {
        $this->name = $name;
        $this->latitude = (float) $latitude;
        $this->longitude = (float) $longitude;
    }

    public function __toString() {
        return "City(name=>$this->name,latitude=>$this->latitude,longitude=>$this->longitude)";
    }

}

class TVSFileParser {

    public $citiesArray = array(); // contatin all cities from file
    private $inputFileName;

    public function __construct($inputFileName) {
        $this->inputFileName = $inputFileName;
        $this->fill();
    }

    private function getContextFromFile() {
        return file($this->inputFileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private function fill() {
        $cities = $this->getContextFromFile();
        foreach ($cities as $cityLine) {
            $fields = explode("\t", $cityLine);
            // expected that <city name>[0]\t<latitude>[1]\t<longitude>[2]
            $this->citiesArray[] = new City($fields[0], $fields[1], $fields[2]);
        }
    }

}

class Distance {

    const EARTH_RADIUS = 6372.8;

    private $sLatitude;
    private $dLatitude;
    private $deltaLatitude;
    private $deltaLongitude;

    public function __construct($sourceCity, $destinationCity) {
        $this->sLatitude = deg2rad($sourceCity->latitude);
        $this->dLatitude = deg2rad($destinationCity->latitude);
        $this->deltaLatitude = deg2rad($destinationCity->latitude - $sourceCity->latitude);
        $this->deltaLongitude = deg2rad($destinationCity->longitude - $sourceCity->longitude);
    }

# trivial, significant mistake when cities are close

    public function calculateBySphericalLowOfCosines() {
        $this->deltaLongitude = deg2rad($city2->longitude - $city1->longitude);
        return self::EARTH_RADIUS * acos(sin($this->sLatitude) * sin($this->dLatitude) + cos($this->sLatitude) * cos($this->dLatitude) * cos($this->deltaLongitude));
    }

# more precise, but slower

    public function calculateByHaversineFormula() {

        $sinDLat2 = sin($this->deltaLatitude / 2);
        $sinDLon2 = sin($this->deltaLongitude / 2);
        $a = $sinDLat2 * $sinDLat2 + $sinDLon2 * $sinDLon2 * cos($this->sLatitude) * cos($this->dLatitude);
        return 2 * self::EARTH_RADIUS * atan2(sqrt($a), sqrt(1 - $a));
    }

# less precise, but faster

    public function calculateByEquirectangularApproximation() {
        $x = $this->deltaLongitude * cos(($this->sLatitude + $this->dLatitude) / 2);
        $y = $this->dLatitude - $this->sLatitude;
        return self::EARTH_RADIUS * sqrt($x * $x + $y * $y);
    }

    public function calculate() {
        return $this->calculateByHaversineFormula();
    }

}

function closestCity($city, &$citiesArray) {
    $closestDistance = null;
    $closestCity = reset($citiesArray);
    $closestCityIndex = array_search($closestCity, $citiesArray);
    if (sizeof($citiesArray) > 1) {
        foreach ($citiesArray as $cityIndex => $currentCity) {
            $resolver = new Distance($city, $currentCity);
            $result = $resolver->calculate();
            if ($closestDistance > $result || $closestDistance == null) {
                $closestDistance = $result;
                $closestCity = $currentCity;
                $closestCityIndex = $cityIndex;
            }
        }
    }
    unset($citiesArray[$closestCityIndex]);
    return $closestCity;
}
###########################
# Main
###########################

if ($argc == 1) {
    echo "will use file name by default:" . DEFAULT_INPUT_FILE_NAME . PHP_EOL;
    echo "     -h  --help for help" . PHP_EOL;
}
$fileName = DEFAULT_INPUT_FILE_NAME;

if ($argv[1] == '-h' || $argv[1] == "--help") {
    echo "Usage:$ $argv[0]  <cities.txt>" . PHP_EOL;
    echo " <cities.txt> - txt file that contain <city name> \t <latitude> \t <longitude> \n" . PHP_EOL;
    exit(0);
}

if ($argc == 2) {
    $fileName = $argv[1];
}

$parser = new TVSFileParser($fileName);
$city = array_shift($parser->citiesArray);
print($city->name . PHP_EOL);
$cities = $parser->citiesArray;
while (sizeof($cities) > 0) {
    $city = closestCity($city, $cities);
    print($city->name . PHP_EOL);
}
?>
