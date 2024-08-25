<?php

// src/Service/StringHelper.php

namespace App\Service;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringHelper extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('getTimeZoneTime', [$this, 'getTimeZoneTime']),
        ];
    }

    public function getTimeZoneTime($timezone){
      $timezone_object = new \DateTimeZone($timezone);
      $offset = $timezone_object->getOffset(new \DateTime()) / 3600;
      if ($offset >= 0) {
          return "+".$offset*3600;
      } else {
          return $offset*3600;
      }
    }


}
