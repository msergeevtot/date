<?php
/**
 * @package    Ms\Date
 * @author     Mikhail Sergeev <msergeev06@gmail.com>
 * @copyright  2021 Mikhail Sergeev
 */

namespace Ms;

/**
 * Класс Ms\Date
 * Класс, описывающий тип переменной "Дата и время"
 */
class Date extends \DateTime
{
    const DEFAULT_TIMEZONE     = 'Europe/Moscow';

    const FORMAT_DATE_DB       = 'Y-m-d';

    const FORMAT_DATE_SITE     = 'd.m.Y';

    const FORMAT_TIME_DB       = 'H:i:s';

    const FORMAT_TIME_SITE     = 'H:i:s';

    const FORMAT_DATETIME_DB   = self::FORMAT_DATE_DB . ' ' . self::FORMAT_TIME_DB;

    const FORMAT_DATETIME_SITE = self::FORMAT_DATE_SITE . ' ' . self::FORMAT_TIME_SITE;

    private static function normalizeTimeArray (&$arTime)
    {
        if (!isset($arTime['HOUR']))
        {
            $arTime['HOUR'] = 0;
        }
        if (!isset($arTime['MIN']))
        {
            $arTime['MIN'] = 0;
        }
        if (!isset($arTime['SEC']))
        {
            $arTime['SEC'] = 0;
        }
        foreach ($arTime as $index => $time)
        {
            if (!in_array($index, ['HOUR', 'MIN', 'SEC']))
            {
                unset($arTime[$index]);
            }
            else
            {
                $arTime[$index] = (int)$time;
            }
        }
    }

    /**
     * Конструктор класса Date
     *
     * @param string             $date
     * @param string             $format
     * @param \DateTimeZone|null $timezone
     *
     * @throws \Exception
     */
    public function __construct (string $date = 'now', string $format = 'db', \DateTimeZone $timezone = null)
    {
        //Определяем временнУю зону и верно ли она задана
        if (is_null($timezone) || !in_array($timezone, static::getTimezonesList()))
        {
            $timezone = new \DateTimeZone(self::DEFAULT_TIMEZONE);
        }
        //Если дата не задана, будем использовать текущее время
        if (is_null($date) || $date == 'now')
        {
            parent::__construct('now', $timezone);

            return;
        }
        //Если формат не задан, считаем что это формат БД
        if (!isset($format))
        {
            $format = 'db';
        }
        //В зависимости от формата формируем дату
        switch ($format)
        {
            case 'time': //timestamp
                $dt = parent::createFromFormat('U', $date, $timezone);
                break;
            case 'db': //YYYY-MM-DD
                $dt = parent::createFromFormat('Y-m-d', $date, $timezone);
                break;
            case 'db_datetime': //YYYY-MM-DD HH:II:SS
                $dt = parent::createFromFormat('Y-m-d H:i:s', $date, $timezone);
                break;
            case 'db_time': //HH:II:SS
                $dt = parent::createFromFormat('H:i:s', $date, $timezone);
                break;
            case 'site': //DD.MM.YYYY
                $dt = parent::createFromFormat('d.m.Y', $date, $timezone);
                break;
            case 'site_datetime': //DD.MM.YYYY HH:II:SS
                $dt = parent::createFromFormat('d.m.Y H:i:s', $date, $timezone);
                break;
            case 'site_time': //HH:II:SS
                $dt = parent::createFromFormat('H:i:s', $date, $timezone);
                break;
            default: //Другой формат
                $dt = parent::createFromFormat($format, $date, $timezone);
                break;
        }

        if ($dt === false)
        {
            parent::__construct('now');
        }

        parent::__construct($dt->format('Y-m-d H:i:s'), $timezone);

        unset($dt);
    }

    /**
     * Возвращает строковое представление объекта в формате даты/времени сайта
     *
     * @return string
     */
    public function __toString ()
    {
        return $this->getDateTimeSite();
    }

    /**
     * Добавляет к дате указанное количество рабочих дней
     *
     * @param int $iNumberOfDays
     *
     * @return $this
     */
    public function addSomeWorkDays (int $iNumberOfDays = 1)
    {
        $iNumberOfDays = (int)$iNumberOfDays;
        if ($iNumberOfDays <= 0)
        {
            return $this;
        }

        for ($i = 0; $i < $iNumberOfDays; $i++)
        {
            $this->setNextDay();
            if ($this->isWeekEnd())
            {
                $i--;
            }
        }

        return $this;
    }

    /**
     * Возвращает массив со списком дат между двумя указанными датами
     *
     * @param Date $startDate
     * @param Date $endDate
     *
     * @return array
     */
    public static function createRangeDatesArray (Date $startDate, Date $endDate)
    {
        $arDates = [];
        $date = $startDate->getNewDate();
        while ($date <= $endDate)
        {
            $arDates[] = $date->getNewDate();
            $date->setNextDay();
        }

        return $arDates;
    }

    /**
     * Возвращает текущую или переданную в параметре метку времени в заданном формате
     * Формат задается аналогичный функции date
     *
     * @link  http://php.net/manual/ru/function.date.php
     *
     * @param string $format    Формат возвращаемой даты
     * @param int    $timestamp Секунды с начала эпохи
     *
     * @return string
     */
    public function getDate ($format = "Y-m-d", $timestamp = null)
    {
        if (is_null($timestamp))
        {
            $timestamp = $this->getTimestamp();
        }

        return static::getDateFromTimestamp($format, $timestamp);
    }

    /**
     * Возвращает текущую или переданную в параметре метку времени в формате даты базы данных
     *
     * @param int $timestamp Секунды с начала эпохи
     *
     * @return string
     */
    public function getDateDB ($timestamp = null)
    {
        return $this->getDate('Y-m-d', $timestamp);
    }

    /**
     * Возвращает текущее время или переданное в параметре в формате базы данных
     *
     * @param null|int $timestamp
     *
     * @return string
     */
    public static function getDateDBTimestamp ($timestamp = null)
    {
        return static::getDateFromTimestamp('Y-m-d', $timestamp);
    }

    /**
     * Возвращает формат даты БД
     *
     * @return string
     */
    public static function getDateDbFormat ()
    {
        return self::FORMAT_DATE_DB;
    }

    /**
     * Возвращает текущее время, либо переданное в параметре в заданном формате
     *
     * @param string   $format
     * @param null|int $timestamp
     *
     * @return string
     */
    public static function getDateFromTimestamp ($format = "Y-m-d", $timestamp = null)
    {

        try
        {
            $date = new self();
            if (!is_null($timestamp))
            {
                $date->setTimestamp($timestamp);
            }

            return $date->format($format);
        }
        catch (\Exception $e)
        {
            return date($format, $timestamp);
        }
    }

    /**
     * Возвращает текущую или переданную в параметре метку времени в формате даты сайта
     *
     * @param int $timestamp Секунды с начала эпохи
     *
     * @return string
     */
    public function getDateSite ($timestamp = null)
    {
        return $this->getDate('d.m.Y', $timestamp);
    }

    /**
     * Возвращает формат даты сайта
     *
     * @return string
     */
    public static function getDateSiteFormat ()
    {
        return self::FORMAT_DATE_SITE;
    }

    /**
     * Возвращает текущую или переданную в параметре метку времени в формате даты и времени базы данных
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function getDateTimeDB ($timestamp = null)
    {
        return $this->getDate('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Возвращает формат Даты/времени БД
     *
     * @return string
     */
    public static function getDateTimeDbFormat ()
    {
        return self::FORMAT_DATETIME_DB;
    }

    /**
     * Возвращает текущую или переданную в параметре метку времени в формате даты и времени сайта
     *
     * @param int $timestamp Секунды с начала эпохи
     *
     * @return string
     */
    public function getDateTimeSite ($timestamp = null)
    {
        return $this->getDate('d.m.Y H:i:s', $timestamp);
    }

    /**
     * Возвращает формат Даты/времени сайта
     *
     * @return string
     */
    public static function getDateTimeSiteFormat ()
    {
        return self::FORMAT_DATETIME_SITE;
    }

    /**
     * Возвращает полное наименование дня недели
     *
     * ('Sunday', 'Monday', 'Tuesday' и т.д.)
     *
     * @param int $day День недели в формате date('w')
     *
     * @return bool|string Полное наименование дня недели, либо false
     */
    public function getNameDayOfWeek ($day = null)
    {
        if (is_null($day))
        {
            $day = $this->format('w');
        }

        switch ((int)$day)
        {
            case 0:
                return 'Sunday';
            case 1:
                return 'Monday';
            case 2:
                return 'Tuesday';
            case 3:
                return 'Wednesday';
            case 4:
                return 'Thursday';
            case 5:
                return 'Friday';
            case 6:
                return 'Saturday';
            default:
                return false;
        }
    }

    /**
     * Возвращает наименование месяца
     *
     * ('January', 'February', 'March' и т.д.)
     *
     * @param int $month Месяц в формате date('n')
     *
     * @return bool|string Наименование месяца, либо false
     */
    public function getNameMonth ($month = null)
    {
        if (is_null($month))
        {
            $month = $this->format('n');
        }

        switch ((int)$month)
        {
            case 1:
                return 'January';
            case 2:
                return 'February';
            case 3:
                return 'March';
            case 4:
                return 'April';
            case 5:
                return 'May';
            case 6:
                return 'June';
            case 7:
                return 'July';
            case 8:
                return 'August';
            case 9:
                return 'September';
            case 10:
                return 'October';
            case 11:
                return 'November';
            case 12:
                return 'December';
            default:
                return false;
        }
    }

    /**
     * Возвращает краткое наименование месяца
     *
     * ('Jan', 'Feb', 'Mаr' и т.д.)
     *
     * @param int $month Месяц в формате date('n')
     *
     * @return bool|string Наименование месяца, либо false
     */
    public function getNameMonthShort ($month = null)
    {
        if ($nameMonth = $this->getNameMonth($month))
        {
            return substr($nameMonth, 0, 3);
        }

        return false;
    }

    /**
     * Возвращает новый объект даты с данными текущей даты
     *
     * @return Date
     */
    public function getNewDate ()
    {
        return clone($this);
    }

    /**
     * Возвращает краткое наименование дня недели
     * ('Su', 'Mo', 'Tu' и т.д.)
     *
     * @param int|null $day День недели в формате date('w')
     *
     * @return bool|string Краткое наименование дня недели, либо false
     */
    public function getShortNameDayOfWeek ($day = null)
    {
        if ($dayOfWeek = $this->getNameDayOfWeek($day))
        {
            return substr($dayOfWeek, 0, 2);
        }

        return false;
    }

    /**
     * Возвращает текущую или переданную в параметре метку времени в формате времени
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function getTime ($timestamp = null)
    {
        return $this->getDate('H:i:s', $timestamp);
    }

    /**
     * Возвращает формат времени БД
     *
     * @return string
     */
    public static function getTimeDbFormat ()
    {
        return self::FORMAT_TIME_DB;
    }

    /**
     * Возвращает время в формате сайта для текущей даты или для переданного timestamp
     *
     * @param int $timestamp - метка времени unix
     *
     * @return string
     */
    public function getTimeSite ($timestamp = null)
    {
        return $this->getTime($timestamp);
    }

    /**
     * Возвращает формат времени сайта
     *
     * @return string
     */
    public static function getTimeSiteFormat ()
    {
        return self::FORMAT_TIME_SITE;
    }

    /**
     * Возвращает массив со списком возможных временнЫх зон
     *
     * @return array
     */
    public static function getTimezonesList ()
    {
        return [
            'Africa/Abidjan',
            'Africa/Accra',
            'Africa/Addis_Ababa',
            'Africa/Algiers',
            'Africa/Asmara',
            'Africa/Bamako',
            'Africa/Bangui',
            'Africa/Banjul',
            'Africa/Bissau',
            'Africa/Blantyre',
            'Africa/Brazzaville',
            'Africa/Bujumbura',
            'Africa/Cairo',
            'Africa/Casablanca',
            'Africa/Ceuta',
            'Africa/Conakry',
            'Africa/Dakar',
            'Africa/Dar_es_Salaam',
            'Africa/Djibouti',
            'Africa/Douala',
            'Africa/El_Aaiun',
            'Africa/Freetown',
            'Africa/Gaborone',
            'Africa/Harare',
            'Africa/Johannesburg',
            'Africa/Juba',
            'Africa/Kampala',
            'Africa/Khartoum',
            'Africa/Kigali',
            'Africa/Kinshasa',
            'Africa/Lagos',
            'Africa/Libreville',
            'Africa/Lome',
            'Africa/Luanda',
            'Africa/Lubumbashi',
            'Africa/Lusaka',
            'Africa/Malabo',
            'Africa/Maputo',
            'Africa/Maseru',
            'Africa/Mbabane',
            'Africa/Mogadishu',
            'Africa/Monrovia',
            'Africa/Nairobi',
            'Africa/Ndjamena',
            'Africa/Niamey',
            'Africa/Nouakchott',
            'Africa/Ouagadougou',
            'Africa/Porto-Novo',
            'Africa/Sao_Tome',
            'Africa/Tripoli',
            'Africa/Tunis',
            'Africa/Windhoek',

            'America/Adak',
            'America/Anchorage',
            'America/Anguilla',
            'America/Antigua',
            'America/Araguaina',
            'America/Argentina/Buenos_Aires',
            'America/Argentina/Catamarca',
            'America/Argentina/Cordoba',
            'America/Argentina/Jujuy',
            'America/Argentina/La_Rioja',
            'America/Argentina/Mendoza',
            'America/Argentina/Rio_Gallegos',
            'America/Argentina/Salta',
            'America/Argentina/San_Juan',
            'America/Argentina/San_Luis',
            'America/Argentina/Tucuman',
            'America/Argentina/Ushuaia',
            'America/Aruba',
            'America/Asuncion',
            'America/Atikokan',
            'America/Bahia',
            'America/Bahia_Banderas',
            'America/Barbados',
            'America/Belem',
            'America/Belize',
            'America/Blanc-Sablon',
            'America/Boa_Vista',
            'America/Bogota',
            'America/Boise',
            'America/Cambridge_Bay',
            'America/Campo_Grande',
            'America/Cancun',
            'America/Caracas',
            'America/Cayenne',
            'America/Cayman',
            'America/Chicago',
            'America/Chihuahua',
            'America/Costa_Rica',
            'America/Creston',
            'America/Cuiaba',
            'America/Curacao',
            'America/Danmarkshavn',
            'America/Dawson',
            'America/Dawson_Creek',
            'America/Denver',
            'America/Detroit',
            'America/Dominica',
            'America/Edmonton',
            'America/Eirunepe',
            'America/El_Salvador',
            'America/Fort_Nelson',
            'America/Fortaleza',
            'America/Glace_Bay',
            'America/Godthab',
            'America/Goose_Bay',
            'America/Grand_Turk',
            'America/Grenada',
            'America/Guadeloupe',
            'America/Guatemala',
            'America/Guayaquil',
            'America/Guyana',
            'America/Halifax',
            'America/Havana',
            'America/Hermosillo',
            'America/Indiana/Indianapolis',
            'America/Indiana/Knox',
            'America/Indiana/Marengo',
            'America/Indiana/Petersburg',
            'America/Indiana/Tell_City',
            'America/Indiana/Vevay',
            'America/Indiana/Vincennes',
            'America/Indiana/Winamac',
            'America/Inuvik',
            'America/Iqaluit',
            'America/Jamaica',
            'America/Juneau',
            'America/Kentucky/Louisville',
            'America/Kentucky/Monticello',
            'America/Kralendijk',
            'America/La_Paz',
            'America/Lima',
            'America/Los_Angeles',
            'America/Lower_Princes',
            'America/Maceio',
            'America/Managua',
            'America/Manaus',
            'America/Marigot',
            'America/Martinique',
            'America/Matamoros',
            'America/Mazatlan',
            'America/Menominee',
            'America/Merida',
            'America/Metlakatla',
            'America/Mexico_City',
            'America/Miquelon',
            'America/Moncton',
            'America/Monterrey',
            'America/Montevideo',
            'America/Montserrat',
            'America/Nassau',
            'America/New_York',
            'America/Nipigon',
            'America/Nome',
            'America/Noronha',
            'America/North_Dakota/Beulah',
            'America/North_Dakota/Center',
            'America/North_Dakota/New_Salem',
            'America/Ojinaga',
            'America/Panama',
            'America/Pangnirtung',
            'America/Paramaribo',
            'America/Phoenix',
            'America/Port-au-Prince',
            'America/Port_of_Spain',
            'America/Porto_Velho',
            'America/Puerto_Rico',
            'America/Punta_Arenas',
            'America/Rainy_River',
            'America/Rankin_Inlet',
            'America/Recife',
            'America/Regina',
            'America/Resolute',
            'America/Rio_Branco',
            'America/Santarem',
            'America/Santiago',
            'America/Santo_Domingo',
            'America/Sao_Paulo',
            'America/Scoresbysund',
            'America/Sitka',
            'America/St_Barthelemy',
            'America/St_Johns',
            'America/St_Kitts',
            'America/St_Lucia',
            'America/St_Thomas',
            'America/St_Vincent',
            'America/Swift_Current',
            'America/Tegucigalpa',
            'America/Thule',
            'America/Thunder_Bay',
            'America/Tijuana',
            'America/Toronto',
            'America/Tortola',
            'America/Vancouver',
            'America/Whitehorse',
            'America/Winnipeg',
            'America/Yakutat',
            'America/Yellowknife',

            'Antarctica/Casey',
            'Antarctica/Davis',
            'Antarctica/DumontDUrville',
            'Antarctica/Macquarie',
            'Antarctica/Mawson',
            'Antarctica/McMurdo',
            'Antarctica/Palmer',
            'Antarctica/Rothera',
            'Antarctica/Syowa',
            'Antarctica/Troll',
            'Antarctica/Vostok',

            'Arctic/Longyearbyen',

            'Asia/Aden',
            'Asia/Almaty',
            'Asia/Amman',
            'Asia/Anadyr',
            'Asia/Aqtau',
            'Asia/Aqtobe',
            'Asia/Ashgabat',
            'Asia/Atyrau',
            'Asia/Baghdad',
            'Asia/Bahrain',
            'Asia/Baku',
            'Asia/Bangkok',
            'Asia/Barnaul',
            'Asia/Beirut',
            'Asia/Bishkek',
            'Asia/Brunei',
            'Asia/Chita',
            'Asia/Choibalsan',
            'Asia/Colombo',
            'Asia/Damascus',
            'Asia/Dhaka',
            'Asia/Dili',
            'Asia/Dubai',
            'Asia/Dushanbe',
            'Asia/Famagusta',
            'Asia/Gaza',
            'Asia/Hebron',
            'Asia/Ho_Chi_Minh',
            'Asia/Hong_Kong',
            'Asia/Hovd',
            'Asia/Irkutsk',
            'Asia/Jakarta',
            'Asia/Jayapura',
            'Asia/Jerusalem',
            'Asia/Kabul',
            'Asia/Kamchatka',
            'Asia/Karachi',
            'Asia/Kathmandu',
            'Asia/Khandyga',
            'Asia/Kolkata',
            'Asia/Krasnoyarsk',
            'Asia/Kuala_Lumpur',
            'Asia/Kuching',
            'Asia/Kuwait',
            'Asia/Macau',
            'Asia/Magadan',
            'Asia/Makassar',
            'Asia/Manila',
            'Asia/Muscat',
            'Asia/Nicosia',
            'Asia/Novokuznetsk',
            'Asia/Novosibirsk',
            'Asia/Omsk',
            'Asia/Oral',
            'Asia/Phnom_Penh',
            'Asia/Pontianak',
            'Asia/Pyongyang',
            'Asia/Qatar',
            'Asia/Qyzylorda',
            'Asia/Riyadh',
            'Asia/Sakhalin',
            'Asia/Samarkand',
            'Asia/Seoul',
            'Asia/Shanghai',
            'Asia/Singapore',
            'Asia/Srednekolymsk',
            'Asia/Taipei',
            'Asia/Tashkent',
            'Asia/Tbilisi',
            'Asia/Tehran',
            'Asia/Thimphu',
            'Asia/Tokyo',
            'Asia/Tomsk',
            'Asia/Ulaanbaatar',
            'Asia/Urumqi',
            'Asia/Ust-Nera',
            'Asia/Vientiane',
            'Asia/Vladivostok',
            'Asia/Yakutsk',
            'Asia/Yangon',
            'Asia/Yekaterinburg',
            'Asia/Yerevan',

            'Atlantic/Azores',
            'Atlantic/Bermuda',
            'Atlantic/Canary',
            'Atlantic/Cape_Verde',
            'Atlantic/Faroe',
            'Atlantic/Madeira',
            'Atlantic/Reykjavik',
            'Atlantic/South_Georgia',
            'Atlantic/St_Helena',
            'Atlantic/Stanley',

            'Australia/Adelaide',
            'Australia/Brisbane',
            'Australia/Broken_Hill',
            'Australia/Currie',
            'Australia/Darwin',
            'Australia/Eucla',
            'Australia/Hobart',
            'Australia/Lindeman',
            'Australia/Lord_Howe',
            'Australia/Melbourne',
            'Australia/Perth',
            'Australia/Sydney',

            'Europe/Amsterdam',
            'Europe/Andorra',
            'Europe/Astrakhan',
            'Europe/Athens',
            'Europe/Belgrade',
            'Europe/Berlin',
            'Europe/Bratislava',
            'Europe/Brussels',
            'Europe/Bucharest',
            'Europe/Budapest',
            'Europe/Busingen',
            'Europe/Chisinau',
            'Europe/Copenhagen',
            'Europe/Dublin',
            'Europe/Gibraltar',
            'Europe/Guernsey',
            'Europe/Helsinki',
            'Europe/Isle_of_Man',
            'Europe/Istanbul',
            'Europe/Jersey',
            'Europe/Kaliningrad',
            'Europe/Kiev',
            'Europe/Kirov',
            'Europe/Lisbon',
            'Europe/Ljubljana',
            'Europe/London',
            'Europe/Luxembourg',
            'Europe/Madrid',
            'Europe/Malta',
            'Europe/Mariehamn',
            'Europe/Minsk',
            'Europe/Monaco',
            'Europe/Moscow',
            'Europe/Oslo',
            'Europe/Paris',
            'Europe/Podgorica',
            'Europe/Prague',
            'Europe/Riga',
            'Europe/Rome',
            'Europe/Samara',
            'Europe/San_Marino',
            'Europe/Sarajevo',
            'Europe/Saratov',
            'Europe/Simferopol',
            'Europe/Skopje',
            'Europe/Sofia',
            'Europe/Stockholm',
            'Europe/Tallinn',
            'Europe/Tirane',
            'Europe/Ulyanovsk',
            'Europe/Uzhgorod',
            'Europe/Vaduz',
            'Europe/Vatican',
            'Europe/Vienna',
            'Europe/Vilnius',
            'Europe/Volgograd',
            'Europe/Warsaw',
            'Europe/Zagreb',
            'Europe/Zaporozhye',
            'Europe/Zurich',

            'Indian/Antananarivo',
            'Indian/Chagos',
            'Indian/Christmas',
            'Indian/Cocos',
            'Indian/Comoro',
            'Indian/Kerguelen',
            'Indian/Mahe',
            'Indian/Maldives',
            'Indian/Mauritius',
            'Indian/Mayotte',
            'Indian/Reunion',

            'Pacific/Apia',
            'Pacific/Auckland',
            'Pacific/Bougainville',
            'Pacific/Chatham',
            'Pacific/Chuuk',
            'Pacific/Easter',
            'Pacific/Efate',
            'Pacific/Enderbury',
            'Pacific/Fakaofo',
            'Pacific/Fiji',
            'Pacific/Funafuti',
            'Pacific/Galapagos',
            'Pacific/Gambier',
            'Pacific/Guadalcanal',
            'Pacific/Guam',
            'Pacific/Honolulu',
            'Pacific/Kiritimati',
            'Pacific/Kosrae',
            'Pacific/Kwajalein',
            'Pacific/Majuro',
            'Pacific/Marquesas',
            'Pacific/Midway',
            'Pacific/Nauru',
            'Pacific/Niue',
            'Pacific/Norfolk',
            'Pacific/Noumea',
            'Pacific/Pago_Pago',
            'Pacific/Palau',
            'Pacific/Pitcairn',
            'Pacific/Pohnpei',
            'Pacific/Port_Moresby',
            'Pacific/Rarotonga',
            'Pacific/Saipan',
            'Pacific/Tahiti',
            'Pacific/Tarawa',
            'Pacific/Tongatapu',
            'Pacific/Wake',
            'Pacific/Wallis'
        ];
    }

    /**
     * Возвращает номер недели даты
     *
     * @return int
     */
    public function getWeekNumber ()
    {
        return (int)$this->format('W');
    }

    /**
     * Проверяет совпадают ли даты текущей метки времени и переданной в параметре
     *
     * @param Date $date
     *
     * @return bool
     */
    public function isDateEqual (Date $date)
    {
        return ($date->format('Y-m-d') == $this->format('Y-m-d'));
    }

    /**
     * Сравнивает две даты
     * Возвращает TRUE, если первая дата больше второй
     * Если даты равны и третий параметр TRUE - также возвращает TRUE
     * В остальных случаях возвращает FALSE
     *
     * @param Date $firstDate
     * @param Date $secondDate
     * @param bool $bEqualsTrue
     *
     * @return bool
     */
    public static function isFirstBigger (Date $firstDate, Date $secondDate, $bEqualsTrue = true)
    {
        if ($firstDate->getTimestamp() == $secondDate->getTimestamp())
        {
            return $bEqualsTrue;
        }
        elseif ($firstDate->getTimestamp() > $secondDate->getTimestamp())
        {
            return true;
        }

        return false;
    }

    /**
     * /**
     * Определяет, входит ли указанное время в требуемый диапазон. Время проверяется включительно, т.е. >= и <=
     * From может быть больше To, тогда проверяется два диапазона: From до конца для и от начала дня до To
     *
     * @param Date   $timeNow   Проверяемое время
     * @param string $sFromTime Строковое представление времени ОТ
     * @param string $sToTime   Строковое представление времени ДО
     *
     * @return bool TRUE, если входит в диапазон, FALSE - не входит
     * @throws \Exception
     */
    public static function isTimeBetween (Date $timeNow, string $sFromTime = '00:00:00', string $sToTime = '23:59:59')
    {
        $arFrom = $arTo = [];
        list($arFrom['HOUR'], $arFrom['MIN'], $arFrom['SEC']) = explode(':', $sFromTime);
        self::normalizeTimeArray($arFrom);
        list($arTo['HOUR'], $arTo['MIN'], $arTo['SEC']) = explode(':', $sToTime);
        self::normalizeTimeArray($arTo);

        $checkFrom = ((int)$arFrom['HOUR'] * 60 * 60) + ((int)$arFrom['MIN'] * 60) + (int)$arFrom['SEC'];
        $checkTo = ((int)$arTo['HOUR'] * 60 * 60) + ((int)$arTo['MIN'] * 60) + (int)$arTo['SEC'];
        $bNormal = true;
        if ($checkFrom > $checkTo)
        {
            $bNormal = false;
        }
        $timeFrom = new self();
        $timeFrom->setTime((int)$arFrom['HOUR'], (int)$arFrom['MIN'], (int)$arFrom['SEC']);
        $timeTo = new self();
        $timeTo->setTime((int)$arTo['HOUR'], (int)$arTo['MIN'], (int)$arTo['SEC']);

        if ($bNormal)
        {
            return ($timeNow >= $timeFrom && $timeNow <= $timeTo);
        }
        else
        {
            $timeStartDay = (new self())->setStartDay();
            $timeEndDay = (new self())->setEndDay();

            return (
                ($timeNow >= $timeFrom && $timeNow <= $timeEndDay)
                || ($timeNow >= $timeStartDay && $timeNow <= $timeTo)
            );
        }
    }

    /**
     * Проверяет, является текущая или переданная в параметре метка времени сегодняшним днем
     *
     * @param Date $date
     *
     * @return bool
     */
    public function isToday ($date = null)
    {
        if (is_null($date))
        {
            $date = $this;
        }

        try
        {
            $now = new self();

            return ($date->format('Y-m-d') == $now->format('Y-m-d'));
        }
        catch (\Exception $e)
        {
            return ($date->format('Y-m-d') == date('Y-m-d'));
        }
    }

    /**
     * Возвращает true, если сегодняшний день выходной
     *
     * Если параметр отсутствует или равен true, а также если модуль ms.dates установлен, проверка осуществляется с
     * использованием метода этого модуля. Это позволит считать выходными праздничные дни, а не только субботу и
     * воскресенье. Если передан параметр false, метод фактически смотрит суббота сегодня или воскресенье.
     *
     * @return bool
     */
    public function isWeekEnd ()
    {
        if ($this->format('w') >= 1 && $this->format('w') <= 5)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Отнимает от даты указанное количество рабочих дней
     *
     * @param int $iNumberOfDays
     *
     * @return $this
     */
    public function minusSomeWorkDays ($iNumberOfDays = 1)
    {
        $iNumberOfDays = (int)$iNumberOfDays;
        if ($iNumberOfDays <= 0)
        {
            return $this;
        }

        for ($i = 0; $i < $iNumberOfDays; $i++)
        {
            $this->setPrevDay();
            if ($this->isWeekEnd())
            {
                $i--;
            }
        }

        return $this;
    }

    /**
     * Изменяет дату текущей метки времени при помощи модификаторов
     *
     * @param string $modify Текстовый модификатор даты
     *
     * @return $this
     */
    public function modify ($modify)
    {
        parent::modify($modify);

        return $this;
    }

    /**
     * Устанавливает дату по параметрам
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return $this
     */
    public function setDate ($year, $month, $day)
    {
        parent::setDate($year, $month, $day);

        return $this;
    }

    /**
     * Устанавливает дату из массива
     *
     * @param array $arDate - массив, который может иметь следующие ключи:
     *                      DAY - день
     *                      MONTH - месяц
     *                      YEAR - год
     *                      HOUR - часы
     *                      MIN - минуты
     *                      SEC - секунды.
     *                      При отсутствии какого-либо параметра, его значение
     *                      берется из текущей даты и времени, либо если задан второй параметр,
     *                      то дата берется из него
     * @param Date  $date   - дата, из которой берутся недостающие параметры в массиве
     *
     * @return $this
     */
    public function setDateFromArray ($arDate, Date $date = null)
    {
        if (is_null($date))
        {
            $date = $this;
        }

        if (!isset($arDate['DAY']) || is_null($arDate['DAY']))
        {
            $day = $date->format('j');
        }
        else
        {
            $day = $arDate['DAY'];
        }

        if (!isset($arDate['MONTH']) || is_null($arDate['MONTH']))
        {
            $month = $date->format('n');
        }
        else
        {
            $month = $arDate['MONTH'];
        }

        if (!isset($arDate['YEAR']) || is_null($arDate['YEAR']))
        {
            $year = $date->format('Y');
        }
        else
        {
            $year = $arDate['YEAR'];
        }

        if (!isset($arDate['HOUR']) || is_null($arDate['HOUR']))
        {
            $hour = $date->format('G');
        }
        else
        {
            $hour = $arDate['HOUR'];
        }

        if (!isset($arDate['MIN']) || is_null($arDate['MIN']))
        {
            $min = (int)$date->format('i');
        }
        else
        {
            $min = $arDate['MIN'];
        }

        if (!isset($arDate['SEC']) || is_null($arDate['SEC']))
        {
            $sec = 0;
        }
        else
        {
            $sec = $arDate['SEC'];
        }

        $this->setDate($year, $month, $day);
        $this->setTime($hour, $min, $sec);

        return $this;
    }

    /**
     * Устанавливает число месяца
     *
     * @param int $day Число месяца от 1 до 31 (в зависимости от месяца даты)
     *
     * @return $this
     */
    public function setDay (int $day)
    {
        $month = (int)$this->format('m');
        $year = (int)$this->format('Y');
        $day = (int)$day;
        parent::setDate($year, $month, $day);

        return $this;
    }

    /**
     * Вызывает функцию установки временнОй зоны по умолчанию
     *
     * @param string $timezone
     */
    public static function setDefaultTimezone ($timezone = self::DEFAULT_TIMEZONE)
    {
        if (is_null($timezone) || !in_array($timezone, static::getTimezonesList()))
        {
            $timezone = self::DEFAULT_TIMEZONE;
        }

        date_default_timezone_set($timezone);
    }

    /**
     * Устанавливает конец дня (время 23:59:59) для текущей метки времени
     *
     * @return $this
     */
    public function setEndDay ()
    {
        $this->setTime(23, 59, 59);

        return $this;
    }

    /**
     * Меняет текущую метку времени, устанавливая первый день текущего месяца
     *
     * @return $this
     */
    public function setFirstDayOfMonth ()
    {
        $this->modify('first day of ' . $this->format('F') . ' ' . $this->format('Y'));

        return $this;
    }

    /**
     * Меняет текущую метку времени, устанавливая первый день текущего года
     *
     * @return $this
     */
    public function setFirstDayOfYear ()
    {
        $this->setDate($this->format('Y'), 1, 1);

        return $this;
    }

    /**
     * Устанавливает дату пятницы текущей недели
     *
     * @return $this
     */
    public function setFriday ()
    {
        $this->modify('friday this week');

        return $this;
    }

    /**
     * Меняет текущую метку времени, устанавливая последний день текущего месяца
     *
     * @return $this
     */
    public function setLastDayOfMonth ()
    {
        $this->modify('last day of ' . $this->format('F') . ' ' . $this->format('Y'));

        return $this;
    }

    /**
     * Меняет текущую метку времени, устанавливая последний день текущего года
     *
     * @return $this
     */
    public function setLastDayOfYear ()
    {
        $this->setDate($this->format('Y'), 12, 31);

        return $this;
    }

    /**
     * Устанавливает дату понедельника текущей недели
     *
     * @return $this
     */
    public function setMonday ()
    {
        $this->modify('monday this week');

        return $this;
    }

    /**
     * Устанавливает месяц
     *
     * @param int $month Месяц от 1 до 12
     *
     * @return $this
     */
    public function setMonth ($month)
    {
        $day = (int)$this->format('d');
        $year = (int)$this->format('Y');
        $month = (int)$month;
        parent::setDate($year, $month, $day);

        return $this;
    }

    /**
     * Меняет текущую метку времени на завтрашний день
     *
     * @return $this
     */
    public function setNextDay ()
    {
        $this->modify("+1 days");

        return $this;
    }

    /**
     * Увеличивает текущее время объекта на 1 час
     *
     * @return $this
     */
    public function setNextHour ()
    {
        $this->modify('+ 1 hours');

        return $this;
    }

    /**
     * Меняет текущую метку времени на следующий месяц
     *
     * @return $this
     */
    public function setNextMonth ()
    {
        $this->modify("+1 month");

        return $this;
    }

    /**
     * Увеличивает текущую дату объекта на 7 дней
     *
     * @return $this
     */
    public function setNextWeek ()
    {
        $this->modify('+7 days');

        return $this;
    }

    /**
     * Меняет текущую метку времени на следующий год
     *
     * @return $this
     */
    public function setNextYear ()
    {
        $this->modify("+1 year");

        return $this;
    }

    /**
     * Меняет текущую метку времени на вчерашний день
     *
     * @return $this
     */
    public function setPrevDay ()
    {
        $this->modify("-1 days");

        return $this;
    }

    /**
     * Уменьшает время объекта на 1 час
     *
     * @return $this
     */
    public function setPrevHour ()
    {
        $this->modify('- 1 hours');

        return $this;
    }

    /**
     * Меняет текущую метку времени на предыдущий месяц
     *
     * @return $this
     */
    public function setPrevMonth ()
    {
        $this->modify("-1 month");

        return $this;
    }

    /**
     * Уменьшает дату объекта на 7 дней
     *
     * @return $this
     */
    public function setPrevWeek ()
    {
        $this->modify('-7 days');

        return $this;
    }

    /**
     * Меняет текущую метку времени на предыдущий год
     *
     * @return $this
     */
    public function setPrevYear ()
    {
        $this->modify("-1 year");

        return $this;
    }

    /**
     * Устанавливает дату субботы текущей недели
     *
     * @return $this
     */
    public function setSaturday ()
    {
        $this->modify('saturday this week');

        return $this;
    }

    /**
     * Устанавливает начало дня (время 00:00:00) для текущей метки времени
     *
     * @return $this
     */
    public function setStartDay ()
    {
        $this->setTime(0, 0);

        return $this;
    }

    /**
     * Устанавливает дату воскресенья текущей недели
     *
     * @return $this
     */
    public function setSunday ()
    {
        $this->modify('sunday this week');

        return $this;
    }

    /**
     * Устанавливает дату четверга текущей недели
     *
     * @return $this
     */
    public function setThursday ()
    {
        $this->modify('thursday this week');

        return $this;
    }

    /**
     * Устанавливает время по параметрам
     *
     * @param int $hour         Часы от 0 до 23
     * @param int $minute       Минуты от 0 до 59
     * @param int $second       Секунды от 0 до 59
     * @param int $microseconds Микросекунды от 0
     *
     * @return Date
     */
    public function setTime ($hour, $minute, $second = 0, $microseconds = 0)
    {
        parent::setTime($hour, $minute, $second);

        return $this;
    }

    /**
     * Устанавливает дату вторника текущей недели
     *
     * @return $this
     */
    public function setTuesday ()
    {
        $this->modify('tuesday this week');

        return $this;
    }

    /**
     * Устанавливает дату среды текущей недели
     *
     * @return $this
     */
    public function setWednesday ()
    {
        $this->modify('wednesday this week');

        return $this;
    }

    /**
     * Устанавливает год
     *
     * @param int $year Год от 0000 до 9999
     *
     * @return $this
     */
    public function setYear ($year)
    {
        $month = (int)$this->format('m');
        $day = (int)$this->format('d');
        $year = (int)$year;
        parent::setDate($year, $month, $day);

        return $this;
    }

    /**
     * Обертка функции strtotime для текущей метки времени, либо переданной в параметре
     *
     * Параметры в функции идентичны strtotime {@link http://php.net/manual/ru/function.strtotime.php}
     * за исключением метки времени, так как если она не передана, используется метка времени объекта,
     * а не текущее время
     *
     * @param string $time - строковое представление времени
     * @param int    $now  - метка времени
     *
     * @return $this|null
     */
    public function strToTime ($time, $now = null)
    {
        if (!is_null($now))
        {
            try
            {
                $tmp = new self();
                $tmp->setTimestamp($now);
                $tmp->modify($time);

                return $tmp;
            }
            catch (\Exception $e)
            {
                return null;
            }
        }

        $this->modify($time);

        return $this;
    }
}