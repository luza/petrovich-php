<?php

class Petrovich
{
    const CASE_NOMINATIVE    = -1; // Именительный падеж.
    const CASE_GENITIVE      = 0;  // Родительный падеж.
    const CASE_DATIVE        = 1;  // Дательный падеж.
    const CASE_ACCUSATIVE    = 2;  // Винительный падеж.
    const CASE_INSTRUMENTAL  = 3;  // Творительный падеж.
    const CASE_PREPOSITIONAL = 4;  // Предложный падеж.

    const GENDER_ANDROGYNOUS = 0; // Пол не определен.
    const GENDER_MALE        = 1; // Мужской.
    const GENDER_FEMALE      = 2; // Женский.

    /**
     * @var array
     */
    private $rules;

    /**
     * Конструктор класса Петрович загружаем правила из файла rules.json.
     *
     * @param string $rulesPath
     *
     * @throws Exception
     */
    public function __construct($rulesPath = null)
    {
        if (!$rulesPath) {
            $rulesPath = __DIR__ . '/../../../cloudloyalty/petrovich-rules/rules.json';
        }

        $rulesResource = fopen($rulesPath, 'r');

        if ($rulesResource == false) {
            throw new Exception('Rules file not found.');
        }

        $rulesArray = fread($rulesResource, filesize($rulesPath));

        fclose($rulesResource);

        $this->rules = get_object_vars(json_decode($rulesArray));
    }

    /**
     * Возвращает ФИО с инициалами.
     *
     * @param string $fullName Полное ФИО (именно в таком порядке).
     *
     * @return string
     */
    static public function initial($fullName)
    {
        $tmp = explode(' ', $fullName);

        return $tmp[0]
            . (isset($tmp[1])
                ? ' ' . mb_substr($tmp[1], 0, 1, 'utf-8') . '.'
                : '')
            . (isset($tmp[2])
                ? ' ' . mb_substr($tmp[2], 0, 1, 'utf-8') . '.'
                : '')
            ;
    }

    /**
     * Разбивает ФИО на отдельные строки в массиве.
     *
     * @param string $fullName Полное ФИО (именно в таком порядке).
     *
     * @return array
     */
    static public function divide($fullName)
    {
        $tmp = explode(' ', $fullName);

        return array(
            'lastname'   => $tmp[0],
            'firstname'  => (isset($tmp[1]) ? $tmp[1] : null),
            'middlename' => (isset($tmp[2]) ? $tmp[2] : null),
        );
    }

    /**
     * Определяет пол по отчеству.
     *
     * @param $middlename
     *
     * @return int
     *
     * @throws Exception
     */
    public function detectGender($middlename)
    {
        if (empty($middlename)) {
            throw new Exception('Middlename cannot be empty.');
        }

        switch (mb_substr(mb_strtolower($middlename, 'utf-8'), -4, null, 'utf-8')) {
            case 'оглы':
                return Petrovich::GENDER_MALE;

                break;
            case 'кызы':
                return Petrovich::GENDER_FEMALE;

                break;
        }

        switch (mb_substr(mb_strtolower($middlename, 'utf-8'), -2, null, 'utf-8')) {
            case 'ич':
                return Petrovich::GENDER_MALE;

                break;
            case 'на':
                return Petrovich::GENDER_FEMALE;

                break;
            default:
                return Petrovich::GENDER_ANDROGYNOUS;

                break;
        }
    }

    /**
     * Задаём имя и слоняем его.
     *
     * @param string $firstname
     * @param int    $case
     * @param int    $gender
     *
     * @return bool|string
     *
     * @throws Exception
     */
    public function firstname(
        $firstname,
        $case = Petrovich::CASE_NOMINATIVE,
        $gender = self::GENDER_ANDROGYNOUS
    ) {
        if (empty($firstname)) {
            throw new Exception('Firstname cannot be empty.');
        }

        if ($case === Petrovich::CASE_NOMINATIVE) {
            return $firstname;
        }

        return $this->inflect($firstname, $case, __FUNCTION__, $gender);
    }

    /**
     * Задём отчество и склоняем его.
     *
     * @param string $middlename
     * @param int    $case
     * @param int    $gender
     *
     * @return bool|string
     *
     * @throws Exception
     */
    public function middlename(
        $middlename,
        $case = Petrovich::CASE_NOMINATIVE,
        $gender = self::GENDER_ANDROGYNOUS
    ) {
        if (empty($middlename)) {
            throw new Exception('Middlename cannot be empty.');
        }

        if ($case === Petrovich::CASE_NOMINATIVE) {
            return $middlename;
        }

        return $this->inflect($middlename, $case, __FUNCTION__, $gender);
    }

    /**
     * Задаём фамилию и слоняем её.
     *
     * @param string $lastname
     * @param int    $case
     * @param int    $gender
     *
     * @return bool|string
     *
     * @throws Exception
     */
    public function lastname(
        $lastname,
        $case = Petrovich::CASE_NOMINATIVE,
        $gender = self::GENDER_ANDROGYNOUS
    ) {
        if (empty($lastname)) {
            throw new Exception('Lastname cannot be empty.');
        }

        if ($case === Petrovich::CASE_NOMINATIVE) {
            return $lastname;
        }

        return $this->inflect($lastname, $case, __FUNCTION__, $gender);
    }

    /**
     * Функция проверяет заданное имя,фамилию или отчество на исключение
     * и склоняет.
     *
     * @param string $name
     * @param int    $case
     * @param string $type
     * @param int    $gender
     *
     * @return bool|string
     */
    private function inflect($name, $case, $type, $gender = self::GENDER_ANDROGYNOUS)
    {
        $namesArr = explode('-', $name);
        $result   = array();

        foreach ($namesArr as $arrName) {
            if (($exception = $this->checkException($arrName, $case, $type, $gender)) !== false) {
                $result[] = $exception;
            } else {
                $result[] = $this->findInRules($arrName, $case, $type, $gender);
            }
        }

        return implode('-', $result);
    }

    /**
     * Возвращает ФИО, сконяя его в указанный падеж.
     *
     * @param string $fullName
     * @param int    $case
     * @param int    $gender
     *
     * @return string
     */
    public function inflectFullName($fullName, $case = Petrovich::CASE_NOMINATIVE, $gender = self::GENDER_ANDROGYNOUS)
    {
        $nameDivide = self::divide($fullName);

        if ($nameDivide['middlename'] && $gender == self::GENDER_ANDROGYNOUS) {
            $gender = $this->detectGender($nameDivide['middlename']);
        }

        try {
            $nameDivide['lastname'] = $this->lastname($nameDivide['lastname'], $case, $gender);
        } catch (Exception $e) {
            $nameDivide['lastname'] = '';
        }

        try {
            $nameDivide['firstname'] = $this->firstname($nameDivide['firstname'], $case, $gender);
        } catch (Exception $e) {
            $nameDivide['firstname'] = '';
        }
        try {
            $nameDivide['middlename'] = $this->middlename($nameDivide['middlename'], $case, $gender);
        } catch (Exception $e) {
            $nameDivide['middlename'] = '';
        }

        return $nameDivide['lastname']
            . ' '
            . $nameDivide['firstname']
            . ' '
            . $nameDivide['middlename'];
    }

    /**
     * Поиск в массиве правил.
     *
     * @param string $name
     * @param int    $case
     * @param string $type
     * @param int    $gender
     *
     * @return string
     */
    private function findInRules($name, $case, $type, $gender = self::GENDER_ANDROGYNOUS)
    {
        foreach ($this->rules[$type]->suffixes as $rule) {
            if (!$this->checkGender($rule->gender, $gender)) {
                continue;
            }

            foreach ($rule->test as $lastChar) {
                $lastNameChar = mb_substr(
                    $name,
                    mb_strlen($name, 'utf-8') - mb_strlen($lastChar, 'utf-8'),
                    mb_strlen($lastChar, 'utf-8'),
                    'utf-8'
                );

                if ($lastChar == $lastNameChar) {
                    if ($rule->mods[$case] == '.') {
                        return $name;
                    }

                    return $this->applyRule($rule->mods, $name, $case);
                }
            }
        }

        return $name;
    }

    /**
     * Проверка на совпадение в исключениях.
     *
     * @param string $name
     * @param int    $case
     * @param string $type
     * @param int    $gender
     *
     * @return bool|string
     */
    private function checkException($name, $case, $type, $gender = self::GENDER_ANDROGYNOUS)
    {
        if (!isset($this->rules[$type]->exceptions)) {
            return false;
        }

        $lowerName = mb_strtolower($name, 'utf8');

        foreach ($this->rules[$type]->exceptions as $rule) {
            if (!$this->checkGender($rule->gender, $gender)) {
                continue;
            }

            if (array_search($lowerName, $rule->test) !== false) {
                if ($rule->mods[$case] == '.') {
                    return $name;
                }

                return $this->applyRule($rule->mods, $name, $case);
            }
        }

        return false;
    }

    /**
     * Склоняем заданное слово.
     *
     * @param $mods
     * @param $name
     * @param $case
     *
     * @return string
     */
    private function applyRule($mods, $name, $case)
    {
        $result = mb_substr(
            $name,
            0,
            mb_strlen($name, 'utf-8') - mb_substr_count($mods[$case], '-', 'utf-8'),
            'utf-8'
        );

        $result .= str_replace('-', '', $mods[$case]);

        return $result;
    }

    /**
     * Преобразует строковое обозначение пола в числовое.
     *
     * @param string
     *
     * @return int
     */
    private function getGender($gender)
    {
        switch ($gender) {
            case 'male':
                return Petrovich::GENDER_MALE;

                break;
            case 'female':
                return Petrovich::GENDER_FEMALE;

                break;
            case 'androgynous':
                return Petrovich::GENDER_ANDROGYNOUS;

                break;
        }
    }

    /**
     * Проверяет переданный пол на соответствие установленному.
     *
     * @param string $ruleGender
     * @param string $inputGender
     *
     * @return bool
     */
    private function checkGender($ruleGender, $inputGender)
    {
        return $inputGender === $this->getGender($ruleGender)
            || $this->getGender($ruleGender) === self::GENDER_ANDROGYNOUS;
    }
}
