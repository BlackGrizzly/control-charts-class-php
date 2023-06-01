<?php

// константы
define("X_MAP", "х");
define("R_MAP", "r");
define("MR_MAP", "mr");
define("MX_MAP", "mх");
define("CX_MAP", "cх");
define("PN_MAP", "pn");

/*
для php 7 и выше
define('A2', [1.880, 1.880, 1.023, 0.729, 0.577, 0.483, 0.419, 0.373, 0.337, 0.308]);
define('A4', [1.880, 1.880, 1.190, 0.800, 0.690, 0.550, 0.510, 0.430, 0.410, 0.360]);
define('B3', [0.000, 0.000, 0.000, 0.000, 0.000, 0.030, 0.118, 0.185, 0.239, 0.283]);
define('B4', [3.267, 3.267, 2.568, 2.266, 2.089, 1.970, 1.882, 1.815, 1.761, 1.716]);
define('D3', [0.000, 0.000, 0.000, 0.000, 0.000, 0.000, 0.076, 0.136, 0.184, 0.223]);
define('D4', [3.268, 3.268, 2.575, 2.282, 2.116, 2.004, 1.924, 1.864, 1.816, 1.777]);
define('D2', [1.128, 1.128, 1.693, 2.059, 2.326, 2.534, 2.704, 2.847, 2.970, 3.078]);
define('reD2', [0.8862, 0.8862, 0.5908, 0.4857, 0.4299, 0.3946, 0.3698, 0.3512, 0.3367, 0.3249]);
*/
define('E2', 2.66);

// лимиты
class limitsClass {

    public $top = 0; // верхняя граница
    public $bottom = 0; // нижняя граница
    public $middle = 0; // центральная линия
    public $sig1top = 0; // сигма 1 верхняя
    public $sig1bottom = 0; // сигма 1 нижняя
    public $sig2top = 0; // сигма 2 верхняя
    public $sig2bottom = 0; // сигма 2 нижняя

    public $limit1top = 0; // граница моделей 1 верхняя
    public $limit1bottom = 0; // граница моделей 1 нижняя
    public $limit2top = 0; // граница моделей 2 верхняя
    public $limit2bottom = 0; // граница моделей 2 нижняя

    public function limits() {
		return [
            'bottom' => $this->bottom, 
            'sig2bottom' => $this->sig2bottom, 
            'sig1bottom' => $this->sig1bottom, 
            'middle' => $this->middle, 
            'sig1top' => $this->sig1top,
            'sig2top' => $this->sig2top, 
            'top' => $this->top, 
            'limit1top' => $this->limit1top, 
            'limit2top' => $this->limit2top, 
            'limit1bottom' => $this->limit1bottom, 
            'limit2bottom' => $this->limit2bottom,
        ];
	}
}

class mapLineClass {
    public $points = [];
    public $color;
    public $dash;
    public $name;

    public function __construct($color, $dash, $name) {
        $this->color = $color;
        $this->dash = $dash;
        $this->name = $name;
    }
}

class mapLinesClass {

    public $cLine;
    public $tLine;
    public $bLine;
    public $sig1Line1;
    public $sig1Line2;
    public $sig2Line1;
    public $sig2Line2;

    public function __construct() {
        $this->cLine = new mapLineClass("green", "", "CL");
        $this->tLine = new mapLineClass("red", "", "UCL");
        $this->bLine = new mapLineClass("red", "", "LCL");
        $this->sig1Line1 = new mapLineClass("gray", "10 5", "1sigma");
        $this->sig1Line2 = new mapLineClass("gray", "10 5", "1sigma");
        $this->sig2Line1 = new mapLineClass("gray", "10 5", "2sigma");
        $this->sig2Line2 = new mapLineClass("gray", "10 5", "2sigma");
    }
} 

//  базовый класс карты
class mapClass
{
    // коэффициенты

    // правила
    private $chartRules = [
        'rule1' => ['name' => "Правило 1", 'title' => "Точка лежит выше (ниже) естественных границ процесса" ],
        'rule2' => ['name' => "Правило 2", 'title' => "Из трех последовательных точек две лежат выше (ниже) ЦЛ более чем на два стандартных отклонения" ],
        'rule3' => ['name' => "Правило 3", 'title' => "Из пяти последовательных точек четыре лежат выше (ниже) ЦЛ более чем на одно стандартное отклонение" ],
        'rule4' => ['name' => "Правило 4", 'title' => "Семь последовательных точек лежат выше (ниже) ЦЛ" ],
        'rule5' => ['name' => "Правило 5", 'title' => "Шесть последовательных точек расположены в порядке монотонного возрастания (убывания)" ],
        'rule6' => ['name' => "Правило 6", 'title' => "Среди десяти последовательных точек существует подгруппа из восьми точек (считая слева направо), которая образует монотонно возрастающую (убывающую) последовательность" ],
        'rule7' => ['name' => "Правило 7", 'title' => "Из двух последовательных точек вторая лежит, по крайней мере, на четыре стандартных отклонения выше (ниже) первой" ]
    ];

    // точки
    public $points = [];
    // максимальное значение по x
    public $max_x = 0;
    // максимальное и минимальное значение по y
    public $max_y = 0;
    public $min_y = 0;
    // линии
    public $mapLine;/*[
        'cLine' => [ 'points' => [], 'color' => "green", 'dash' => "", 'name' => "CL" ],
        'tLine' => [ 'points' => [], 'color' => "red", 'dash' => "", 'name' => "UCL" ],
        'bLine' => [ 'points' => [], 'color' => "red", 'dash' => "", 'name' => "LCL" ],
        'sig1Line1' => [ 'points' => [], 'color' => "gray", 'dash' => "10 5", 'name' => "1sigma" ],
        'sig1Line2' => [ 'points' => [], 'color' => "gray", 'dash' => "10 5", 'name' => "1sigma" ],
        'sig2Line1' => [ 'points' => [], 'color' => "gray", 'dash' => "10 5", 'name' => "2sigma" ],
        'sig2Line2' => [ 'points' => [], 'color' => "gray", 'dash' => "10 5", 'name' => "2sigma" ]
    ];*/
    // ось x
    public $xAxis = [
        'caption' => "",
        'offset' => 0
    ];
    // ось y
    public $yAxis = [
        'caption' => "",
        'offset' => 0
    ];
    // название
    public $caption = "";
    // конструктор
    public function __construct() {
        $this->mapLine = new mapLinesClass();
    }
    // проверка правил
    public function check_rules($rules) {
        $pass = true;
        $rule = "";
        $tmp = 0;
        $points = [];
        $rule2 = [ 'up' => [], 'down' => [] ];
        $rule3 = [ 'up' => [], 'down' => [] ];
        $rule4 = [];
        $rule5 = [];
        $rule5_prev = null;
        //rule6={up: [], upPrev: null, down: [], downPrev: null},
        $rule6 = [];
        $rule7_prev = null;
        //функция проверки правила 6
        function check_rule6($rule_array) {
            $up = [];
            $down = [];
            $prevUp = null;
            $prevDown = null;
            $prev = null;
            $res = false;

            for ($a1 = count($rule_array) - 1; $a1 >= 0; $a1--) {
                for ($a2 = count($rule_array) - 1; $a2 >= 0; $a2--) {
                    if ($a2 == $a1) continue;
                    $up = [];
                    $down = [];
                    $prevUp = null;
                    $prevDown = null;
                    $prev = null;
                    $res = false;
                    for ($i = 0; $i < count($rule_array); $i++) {
                        if ($i == $a1 && count($rule_array) > 9) continue;
                        if ($i == $a2 && count($rule_array) > 8) continue;
                        if ($prev == null)
                            $prev = $rule_array[$i];
                        if ($prevUp == null && $prev < $rule_array[$i]) {
                            array_push($up, 1);
                            $prevUp = $rule_array[$i];
                        } else {
                            if ($prevUp < $rule_array[$i]) {
                                array_push($up, 1);
                                $prevUp = $rule_array[$i];
                            }
                        }
                        if ($prevDown == null && $prev > $rule_array[$i]) {
                            array_push($down, 1);
                            $prevDown = $rule_array[$i];
                        } else {
                            if ($prevDown > $rule_array[$i]) {
                                array_push($down, 1);
                                $prevDown = $rule_array[$i];
                            }
                        }
                    }
                    $res = array_sum($up) == 8;
                    if ($res) return !$res;
                    $res = array_sum($down) == 8;
                    if ($res) return !$res;
                    if (count($rule_array) == 8) break;
                }
                if (count($rule_array) == 9) break;
            }
            return !$res;
        }
        // получение крайних точек
        function get_points($map, $i, $count) {
            $cnt = 0;
            $points_array = [$i + 1];
            for ($p = $i; $p >= 0 && $cnt < $count; $p--) {
                if (isset($map[$p]))
                    $cnt++;
            }
            array_unshift($points_array, $p + 2);
            return $points_array;
        };
        // проход по точкам
        for ($i = 0; $i < count($this->points); $i++) {
            if ($this->points[$i] == null || !$this->points[$i][2])
                continue;
            // проверка правила 1
            if (isset($rules[1]) && $rules[1]) {
                if (count($this->mapLine->tLine->points) > 2)
                    $limit = $this->mapLine->tLine->points[$i][1];
                else
                    $limit = $this->mapLine->tLine->points[0][1];
                if ($this->points[$i][1] >= $limit) {
                    $pass = false;
                    $rule = "rule1";
                    $points = [$i + 1, $i + 1];
                }
                if (!$pass) break;
                if (count($this->mapLine->bLine->points) > 2)
                    $limit = $this->mapLine->bLine->points[$i][1];
                else
                    $limit = $this->mapLine->bLine->points[0][1];
                if ($this->points[$i][1] <= $limit) {
                    $pass = false;
                    $rule = "rule1";
                    $points = [$i + 1, $i + 1];
                }
                if (!$pass) break;
            }
            // проверка правила 2
            if (isset($rules[2]) && $rules[2]) {
                if (count($this->mapLine->sig2Line1->points) > 2)
                    $limit = $this->mapLine->sig2Line1->points[$i][1];
                else
                    $limit = $this->mapLine->sig2Line1->points[0][1];
                if ($this->points[$i][1] >= $limit) {
                    array_push($rule2['up'], 1);
                    $pass = !(array_sum($rule2['up']) == 2 && count($rule2['up']) <= 3);
                } else {
                    array_push($rule2['up'], 0);
                }
                if (!$pass) {
                    $rule = "rule2";
                    $points = get_points($this->points, $i, 3);
                    break;
                }
                if (count($this->mapLine->sig2Line2->points) > 2)
                    $limit = $this->mapLine->sig2Line2->points[$i][1];
                else
                    $limit = $this->mapLine->sig2Line2->points[0][1];
                if ($this->points[$i][1] <= $limit) {
                    array_push($rule2['down'], 1);
                    $pass = !(array_sum($rule2['down']) == 2 && count($rule2['down']) <= 3);
                } else {
                    array_push($rule2['down'], 0);
                }
                if (!$pass) {
                    $rule = "rule2";
                    $points = get_points($this->points, $i, 3);
                    break;
                }
                if (count($rule2['up']) == 3)
                    array_shift($rule2['up']);
                if (count($rule2['down']) == 3)
                    array_shift($rule2['down']);
            }
            // проверка правила 3
            if (isset($rules[3]) && $rules[3]) {
                if (count($this->mapLine->sig1Line1->points) > 2)
                    $limit = $this->mapLine->sig1Line1->points[$i][1];
                else
                    $limit = $this->mapLine->sig1Line1->points[0][1];
                if ($this->points[$i][1] >= $limit) {
                    array_push($rule3['up'], 1);
                    $pass = !(array_sum($rule3['up']) == 4 && count($rule3['up']) <= 5);
                } else {
                    array($rule3['up'], 0);
                }
                if (!$pass) {
                    $rule = "rule3";
                    $points = get_points($this->points, $i, 5);
                    break;
                }
                if (count($this->mapLine->sig1Line2->points) > 2)
                    $limit = $this->mapLine->sig1Line2->points[$i][1];
                else
                    $limit = $this->mapLine->sig1Line2->points[0][1];
                if ($this->points[$i][1] <= $limit) {
                    array_push($rule3['down'], 1);
                    $pass = !(array_sum($rule3['down']) == 4 && count($rule3['down']) <= 5);
                } else {
                    array_push($rule3['down'], 0);
                }
                if (!$pass) {
                    $rule = "rule3";
                    $points = get_points($this->points, $i, 5);
                    break;
                }
                if (count($rule3['up']) == 5)
                    array_shift($rule3['up']);
                if (count($rule3['down']) == 5)
                    array_shift($rule3['down']);
            }
            // проверка правила 4
            if (isset($rules[4]) && $rules[4]) {
                $limit = $this->mapLine->cLine->points[0][1];
                if ($this->points[$i][1] >= $limit) {
                    array_push($rule4, 1);
                    $pass = !(array_sum($rule4) == 7 && count($rule4) == 7);
                }
                if (!$pass) {
                    $rule = "rule4";
                    $points = get_points($this->points, $i, 7);
                    break;
                }
                if ($this->points[$i][1] <= $limit) {
                    array_push($rule4, -1);
                    $pass = !(array_sum($rule4) == -7 && count($rule4) == 7);
                }
                if (!$pass) {
                    $rule = "rule4";
                    $points = get_points($this->points, $i, 7);
                    break;
                }
                if ($this->points[$i][1] == $limit) {
                    array_push($rule4, 0);
                }
                if (count($rule4) == 7)
                    array_shift($rule4);
            }
            // проверка правила 5
            if (isset($rules[5]) && $rules[5]) {
                if ($rule5_prev != null) {
                    if ($this->points[$i][1] > $rule5_prev) {
                        array_push($rule5, 1);
                        $pass = !(array_sum($rule5) == 5 && count($rule5) == 5);
                    }
                    if (!$pass) {
                        $rule = "rule5";
                        $points = get_points($this->points, $i, 6);
                        break;
                    }
                    if ($this->points[$i][1] < $rule5_prev) {
                        array_push($rule5, -1);
                        $pass = !(array_sum($rule5) == -5 && count($rule5) == 5);
                    }
                    if (!$pass) {
                        $rule = "rule5";
                        $points = get_points($this->points, $i, 6);
                        break;
                    }
                    if ($this->points[$i][1] == $rule5_prev) {
                        array_push($rule5, 0);
                    }
                    if (count($rule5) == 5)
                        array_shift($rule5);
                }
                $rule5_prev = $this->points[$i][1];
            }
            // проверка правила 6
            if (isset($rules[6]) && $rules[6]) {
                array_push($rule6, $this->points[$i][1]);
                if (count($rule6) >= 8) {
                    $pass = check_rule6($rule6);
                }
                if (!$pass) {
                    $rule = "rule6";
                    $points = get_points($this->points, $i, 10);
                    break;
                }
                if (count($rule6) == 10)
                    array_shift($rule6);
            }
            // проверка правила 7
            if (isset($rules[7]) && $rules[7]) {
                if ($rule7_prev != null) {
                    if (count($this->mapLine->sig1Line1->points) > 2)
                        $diff = $this->mapLine->sig1Line1->points[$i][1] - $this->mapLine->cLine->points[0][1];
                    else
                        $diff = $this->mapLine->sig1Line1->points[0][1] - $this->mapLine->cLine->points[0][1];
                    if ((abs($this->points[$i][1] - $rule7_prev) / $diff) >= 4) {
                        $pass = false;
                        $rule = "rule7";
                        $points = [$i, $i + 1];
                        break;
                    }
                }
                $rule7_prev = $this->points[$i][1];
            }
        }
        if ($pass)
            return ['text' => "Предсказуемый",  'rule' => [], 'points' => []];
        else
            return ['text' => "Непредсказуемый!", 'rule' => $this->chartRules[$rule], 'points' => $points];
    }
}
// класс x карты
class xMapClass {
    // карты
    public $X_MAP;
    public $MR_MAP;

    //private $A2 = [1.880, 1.880, 1.023, 0.729, 0.577, 0.483, 0.419, 0.373, 0.337, 0.308];
    //private $A4 = [1.880, 1.880, 1.190, 0.800, 0.690, 0.550, 0.510, 0.430, 0.410, 0.360];
    //private $B3 = [0.000, 0.000, 0.000, 0.000, 0.000, 0.030, 0.118, 0.185, 0.239, 0.283];
    //private $B4 = [3.267, 3.267, 2.568, 2.266, 2.089, 1.970, 1.882, 1.815, 1.761, 1.716];
    //private $D3 = [0.000, 0.000, 0.000, 0.000, 0.000, 0.000, 0.076, 0.136, 0.184, 0.223];
    private $D4 = [3.268, 3.268, 2.575, 2.282, 2.116, 2.004, 1.924, 1.864, 1.816, 1.777];
    //private $D2 = [1.128, 1.128, 1.693, 2.059, 2.326, 2.534, 2.704, 2.847, 2.970, 3.078];
    //private $reD2 = [0.8862, 0.8862, 0.5908, 0.4857, 0.4299, 0.3946, 0.3698, 0.3512, 0.3367, 0.3249];
    //private $E2 = 2.66;

    // конструктор
    public function __construct() {
        $this->X_MAP = new mapClass();
        $this->MR_MAP = new mapClass();
        $this->hideMinus = false;
    }
    // вычисление контрольной карты
    public function calc(&$limits = null, $fix_limit = false) {
        $prev_value = null;
        $sum = 0;
        $sum2 = 0;
        $value = 0;
        $value2 = 0;
        $count = 0;
        $count2 = 0;
        $max = $this->X_MAP->max_y;
        $max2 = 0;
        $min = $this->X_MAP->min_y;
        $min2 = null;
        $point = 0;
        $sigma = 0;
        $e_count = $this->X_MAP->max_x;

        if ($limits === null) {
            $limits = new limitsClass();
        }

        // TODO: max_x может не быть равным кол-во точек!

		for ($i = 1; $i <= $e_count; $i++) {
			if ($this->X_MAP->points[$i - 1][2]) {
				$value = $this->X_MAP->points[$i - 1][1];
				if ($prev_value != null) {
					$value2 = abs($prev_value - $value);
					if ($min2 == null)
                        $min2 = $value2;
					if ($value2 > $max2)
                        $max2 = $value2;
					if ($value2 < $min2)
                        $min2 = $value2;
					//if (i<=limit_points)
					{
						$sum2 += $value2;
						$count2++;
					}
					array_push($this->MR_MAP->points, [$i, $value2]);
				}
				$prev_value = $value;
				//if (i<=limit_points)
				{
					$sum += $value;
					$count++;
				}
			}
		}
		if ($fix_limit) {
			$point = $limits->middle;
			$this->X_MAP->mapLine->cLine->points = [[1, $point], [$e_count, $point]];
			$point = $limits->top;
			$this->X_MAP->mapLine->tLine->points = [[1, $point], [$e_count, $point]];
			if ($point > $max)
				$max = $point;
			$point = $limits->sig1top;
			$this->X_MAP->mapLine->sig1Line1->points = [[1, $point], [$e_count, $point]];
			$point = $limits->sig2top;
			$this->X_MAP->mapLine->sig2Line1->points = [[1, $point], [$e_count, $point]];
			$point = $limits->bottom;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->X_MAP->mapLine->bLine->points = [[1, $point], [$e_count, $point]];
			if ($point < $min)
				$min = $point;
			$point = $limits->sig1bottom;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->X_MAP->mapLine->sig1Line2->points = [[1, $point], [$e_count, $point]];
			$point = $limits->sig2bottom;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->X_MAP->mapLine->sig2Line2->points = [[1, $point], [$e_count, $point]];

			//расчеты mr карты
			$point = round($sum2 / $count2, 1);
			$this->MR_MAP->mapLine->cLine->points = [[1, $point], [$e_count, $point]];

			$point = $this->D4[0] * $this->MR_MAP->mapLine->cLine->points[0][1];
			$this->MR_MAP->mapLine->tLine->points = [[1, $point], [$e_count, $point]];
			if ($point > $max2)
				$max2 = $point;
			$this->MR_MAP->mapLine->bLine->points = [[1, 0], [$e_count, 0]];
			$sigma = ($this->MR_MAP->mapLine->tLine->points[0][1] - $this->MR_MAP->mapLine->cLine->points[0][1]) / 3;

			$point = $this->MR_MAP->mapLine->cLine->points[0][1] + $sigma;
			$this->MR_MAP->mapLine->sig1Line1->points = [[1, $point], [$e_count, $point]];

			$point = $this->MR_MAP->mapLine->cLine->points[0][1] + $sigma * 2;
			$this->MR_MAP->mapLine->sig2Line1->points = [[1, $point], [$e_count, $point]];

			$point = $this->MR_MAP->mapLine->cLine->points[0][1] - $sigma;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->MR_MAP->mapLine->sig1Line2->points = [[1, $point], [$e_count, $point]];

			$point = $this->MR_MAP->mapLine->cLine->points[0][1] - $sigma * 2;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->MR_MAP->mapLine->sig2Line2->points = [[1, $point], [$e_count, $point]];
			if ($this->hideMinus && $min2 < 0)
				$min2 = 0;
		} else {
			$point = round($sum / $count, 1);
			$this->X_MAP->mapLine->cLine->points = [[1, $point], [$e_count, $point]];
			$limits->middle = $point;
			$point = round($sum2 / $count2, 1);
			$this->MR_MAP->mapLine->cLine->points = [[1, $point], [$e_count, $point]];
			$point = $this->X_MAP->mapLine->cLine->points[0][1] + E2 * $this->MR_MAP->mapLine->cLine->points[0][1];
			$this->X_MAP->mapLine->tLine->points = [[1, $point], [$e_count, $point]];
			$limits->top = $point;
			if ($point > $max)
				$max = $point;
			$point = $this->X_MAP->mapLine->cLine->points[0][1] + E2 * $this->MR_MAP->mapLine->cLine->points[0][1] / 3;
			$this->X_MAP->mapLine->sig1Line1->points = [[1, $point], [$e_count, $point]];
			$limits->sig1top = $point;
			$point = $this->X_MAP->mapLine->cLine->points[0][1] + E2 * $this->MR_MAP->mapLine->cLine->points[0][1] / 3 * 2;
			$this->X_MAP->mapLine->sig2Line1->points = [[1, $point], [$e_count, $point]];
			$limits->sig2top = $point;
			$point = $this->X_MAP->mapLine->cLine->points[0][1] - E2 * $this->MR_MAP->mapLine->cLine->points[0][1];
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->X_MAP->mapLine->bLine->points = [[1, $point], [$e_count, $point]];
			$limits->bottom = $point;
			if ($point < $min)
				$min = $point;
			$point = $this->X_MAP->mapLine->cLine->points[0][1] - E2 * $this->MR_MAP->mapLine->cLine->points[0][1] / 3;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->X_MAP->mapLine->sig1Line2->points = [[1, $point], [$e_count, $point]];
			$limits->sig1bottom = $point;
			$point = $this->X_MAP->mapLine->cLine->points[0][1] - E2 * $this->MR_MAP->mapLine->cLine->points[0][1] / 3 * 2;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->X_MAP->mapLine->sig2Line2->points = [[1, $point], [$e_count, $point]];
			$limits->sig2bottom = $point;
			//расчеты mr карты
			$point = $this->D4[0] * $this->MR_MAP->mapLine->cLine->points[0][1];
			$this->MR_MAP->mapLine->tLine->points = [[1, $point], [$e_count, $point]];
			if ($point > $max2)
				$max2 = $point;
			$this->MR_MAP->mapLine->bLine->points = [[1, 0], [$e_count, 0]];
			$sigma = ($this->MR_MAP->mapLine->tLine->points[0][1] - $this->MR_MAP->mapLine->cLine->points[0][1]) / 3;
			$point = $this->MR_MAP->mapLine->cLine->points[0][1] + $sigma;
			$this->MR_MAP->mapLine->sig1Line1->points = [[1, $point], [$e_count, $point]];
			$point = $this->MR_MAP->mapLine->cLine->points[0][1] + $sigma * 2;
			$this->MR_MAP->mapLine->sig2Line1->points = [[1, $point], [$e_count, $point]];
			$point = $this->MR_MAP->mapLine->cLine->points[0][1] - $sigma;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->MR_MAP->mapLine->sig1Line2->points = [[1, $point], [$e_count, $point]];
			$point = $this->MR_MAP->mapLine->cLine->points[0][1] - $sigma * 2;
			if ($this->hideMinus && $point < 0)
				$point = 0;
			$this->MR_MAP->mapLine->sig2Line2->points = [[1, $point], [$e_count, $point]];
			if ($this->hideMinus && $min2 < 0)
				$min2 = 0;
		}

		$this->X_MAP->max_y = ceil($max);
		$this->MR_MAP->max_y = ceil($max2);
		$this->X_MAP->min_y = floor($min);
		$this->MR_MAP->min_y = floor($min2);

		$range = ($limits->top - $limits->middle) / 5;
		$limits->limit1top = $limits->middle + $range;
		$limits->limit2top = $limits->limit1top + (2 * $range);
		$limits->limit1bottom = $limits->middle - $range;
		$limits->limit2bottom = $limits->limit1bottom - (2 * $range);

		return $this;
	}

    public function points(&$points, $grouped = false, $max_point = null, $min_point = null) {

		if (count($points) === 1) {
			array_push($points, [2, $points[0][1]]);
		}
		if ($grouped) {
			foreach ($points as $gp_points) {
                // TODO: ????
				$this->X_MAP->points = array_merge($this->X_MAP->points, $gp_points.items);
				$this->X_MAP->max_x += count($gp_points);
			}
		} else {
			$this->X_MAP->points = $points;
			$this->X_MAP->max_x = count($points);
            // TODO: max_x может не быть равным кол-во точек!
		}
		if ($max_point != null) {
			$this->X_MAP->max_y = $max_point;
		} else {
            foreach ($this->X_MAP->points as $point) {
                if ($point[1] > $this->X_MAP->max_y)
                    $this->X_MAP->max_y = $point[1];
            }
		}
		if ($min_point != null) {
			$this->X_MAP->min_y = $min_point;
		} else {
            foreach ($this->X_MAP->points as $point) {
                if ($point[1] < $this->X_MAP->min_y)
                    $this->X_MAP->min_y = $point[1];
            }
		}

		return $this;
	}

    // показывать отрицательные значения
    public function setHideMinus($enable = false) {
        $this->hideMinus = $enable;
        return $this;
    }
}

/*$points = [
    [1, 23, true, 'id1'], 
    [2, 15, true, 'id2'], 
    [3, 17, true, 'id3'], 
    [4, 19, true, 'id4'], 
    [5, 21, true, 'id5'], 
    [6, 22, true, 'id6'], 
    [7, 23, true, 'id7']
];
$limit = new limitsClass();
$map = new xMapClass();

$map->points($points);
$map->calc($limit);

var_dump($map->X_MAP->check_rules([1=>true, 2=>true, 3=>true, 4=>true, 5=>true, 6=>true, 7=> true]));

var_dump($map->X_MAP->points);*/


?>