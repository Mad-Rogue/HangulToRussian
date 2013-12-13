/*
Copyright [2011] [Dmitry Chernov] [http://www.mad-rogue.com]

Licensed under the Apache License, Version 2.0 (the �License�); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an �AS IS� BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
*/

<?php
function flog($text) { 
    $headers = apache_request_headers();
    $fname = "logs/".date("Y-m-d").".log";

    if(file_exists($fname)) {
      $fp = fopen($fname, "a" );
    } else {
      $fp = fopen($fname, "w" );
    }

    fwrite($fp, "[".$headers["X-Real-IP"]."]");
//    fwrite($fp, "[".$_SERVER["REMOTE_ADDR"]."]");
    fwrite($fp, "  ".date("Y-m-d H:i:s")."  ");
    if(isset($_SERVER['HTTP_REFERER'])) {
       fwrite($fp, $_SERVER["HTTP_REFERER"]."  ");
    }
    fwrite($fp, $_SERVER["HTTP_USER_AGENT"].")\n");
    fwrite($fp, "$text\n");
    fclose($fp);
}

function utf8_to_unicode( $str ) {
    $unicode= array();
    $values= array();
    $lookingFor= 1;

    for ($i= 0; $i < strlen( $str ); $i++ ) {
        $thisValue = ord( $str[ $i ] );
        if ( $thisValue < 128 ) $unicode[] = $thisValue;
        else {
            if (count( $values ) == 0)
                $lookingFor = ($thisValue < 224) ? 2 : 3;
            $values[] = $thisValue;
            if (count($values) == $lookingFor ) {
                $number= ($lookingFor == 3) ?
                    (($values[0]%16)*4096)+(($values[1]%64)*64)+($values[2]%64):
                        (($values[0]%32)*64)+($values[1]%64);
                $unicode[]= $number;
                $values= array();
                $lookingFor= 1;
            }
        }
    }
    return $unicode;
}
function unicode_to_entities( $unicode ) {
    $entities = '';
    foreach( $unicode as $value ) {
        $v=sprintf("%0x",$value);
        $entities .= '&#' . $v . ';';
    }
    return $entities;
}

function unicode_to_entities_preserving_ascii( $unicode ) {
    $entities = '';
    foreach( $unicode as $value ) {
        $entities .= ( $value > 127 ) ? '&#' . $value . ';' : chr( $value );
    }
    return $entities;
}


// for Hangul
function utf8_hangul_to_jamo($str) {
    static $j2c=array(
        0x3131=>0x11a8,
        0x3132=>0x11a9,
        0x3133=>0x11aa,
        0x3134=>0x11ab,
        0x3135=>0x11ac,
        0x3136=>0x11ad,
        0x3137=>0x11ae,
        0x3139=>0x11af,
        0x313a=>0x11b0,
        0x313b=>0x11b1,
        0x313c=>0x11b2,
        0x313d=>0x11b3,
        0x313e=>0x11b4,
        0x313f=>0x11b5,
        0x3140=>0x11b6,
        0x3141=>0x11b7,
        0x3142=>0x11b8,
        0x3144=>0x11b9,
        0x3145=>0x11ba,
        0x3146=>0x11bb,
        0x3147=>0x11bc,
        0x3148=>0x11bd,
        0x314a=>0x11be,
        0x314b=>0x11bf,
        0x314c=>0x11c0,
        0x314d=>0x11c1,
        0x314e=>0x11c2,
        0x314f=>0x1161,
        0x3150=>0x1162,
        0x3151=>0x1163,
        0x3152=>0x1164,
        0x3153=>0x1165,
        0x3154=>0x1166,
        0x3155=>0x1167,
        0x3156=>0x1168,
        0x3157=>0x1169,
        0x3158=>0x116a,
        0x3159=>0x116b,
        0x315a=>0x116c,
        0x315b=>0x116d,
        0x315c=>0x116e,
        0x315d=>0x116f,
        0x315e=>0x1170,
        0x315f=>0x1171,
        0x3160=>0x1172,
        0x3161=>0x1173,
        0x3162=>0x1174,
        0x3163=>0x1175,
    );
    $jamo=array();
    $unicode=utf8_to_unicode($str);
    foreach ($unicode as $u) {
        if ($u >= 0xac00 and $u <=0xd7af) {
            $dummy=$u - 0xac00;
            $T= $dummy % 28 + 0x11a7;
            $dummy=(int)($dummy/28);
            $V= $dummy % 21 + 0x1161;
            $dummy=(int)($dummy/21);
            $L= $dummy + 0x1100;
            $jamo[]=$L;$jamo[]=$V;
            if ($T >=0x11a8) $jamo[]=$T;
        } else if ($u >=0x3130 and $u <=0x318f) {
            $jamo[]=$j2c[$u];
        } else {
            $jamo[]=$u;
        }
    }
    return $jamo;
}

function jamo_to_syllable($jamo) {
    define('hangul_base', 0xac00);
    define('choseong_base', 0x1100);
    define('jungseong_base', 0x1161);
    define('jongseong_base', 0x11a7);
    define('njungseong', 21);
    define('njongseong', 28);

    if (sizeof($jamo)<=3) {
        $choseong=$jamo[0];
        $jungseong=$jamo[1];
        $jongseong=isset($jamo[2]) ? $jamo[2]:0;
    }

    if ($jongseong == 0)
    $jongseong = 0x11a7; 

    if (!($choseong  >= 0x1100 && $choseong  <= 0x1112))
    return 0;
    if (!($jungseong >= 0x1161 && $jungseong <= 0x1175))
    return 0;
    if (!($jongseong >= 0x11a7 && $jongseong <= 0x11c2))
    return 0;

    $choseong  -= choseong_base;
    $jungseong -= jungseong_base;
    $jongseong -= jongseong_base;
    // php hack XXX
    $choseong = sprintf("%d",$choseong);
    $jungseong = sprintf("%d",$jungseong);
    $jongseong = sprintf("%d",$jongseong);

    $ch[0] = (($choseong * njungseong) + $jungseong) * njongseong + $jongseong
    + hangul_base;
    return $ch;
}
function unicode_to_utf8( $str ) {
    $utf8 = '';
    foreach( $str as $unicode ) {
        if ( $unicode < 128 ) {
            $utf8.= chr( $unicode );
        } elseif ( $unicode < 2048 ) {
            $utf8.= chr( 192 +  ( ( $unicode - ( $unicode % 64 ) ) / 64 ) );
            $utf8.= chr( 128 + ( $unicode % 64 ) );
        } else {
            $utf8.= chr( 224 + ( ( $unicode - ( $unicode % 4096 ) ) / 4096 ) );
            $utf8.= chr( 128 + ( ( ( $unicode % 4096 ) - ( $unicode % 64 ) ) / 64 ) );
            $utf8.= chr( 128 + ( $unicode % 64 ) );
        }
    }
    return $utf8;
}

function unicodechar_to_utf8( $unicode ) {
    $utf8 = '';
        if ( $unicode < 128 ) {
            $utf8.= chr( $unicode );
        } elseif ( $unicode < 2048 ) {
            $utf8.= chr( 192 +  ( ( $unicode - ( $unicode % 64 ) ) / 64 ) );
            $utf8.= chr( 128 + ( $unicode % 64 ) );
        } else {
            $utf8.= chr( 224 + ( ( $unicode - ( $unicode % 4096 ) ) / 4096 ) );
            $utf8.= chr( 128 + ( ( ( $unicode % 4096 ) - ( $unicode % 64 ) ) / 64 ) );
            $utf8.= chr( 128 + ( $unicode % 64 ) );
        }
    return $utf8;
}

$cho_list = utf8_to_unicode("ㄱㄲㄴㄷㄸㄹㅁㅂㅃㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎ");
$jung_list = utf8_to_unicode("ㅏㅐㅑㅒㅓㅔㅕㅖㅗㅘㅙㅚㅛㅜㅝㅞㅟㅠㅡㅢㅣ");
$jong_list = utf8_to_unicode(" ㄱㄲㄳㄴㄵㄶㄷㄹㄺㄻㄼㄽㄾㄿㅀㅁㅂㅄㅅㅆㅇㅈㅊㅋㅌㅍㅎ");

function getCho($idx) {
	$cho_list = $GLOBALS["cho_list"];
	if($idx>=0 && $idx<count($cho_list)) {
		return $cho_list[$idx];
	}

	return false;
}


function getChoIdx($ch) {
	$cho_list = $GLOBALS["cho_list"];
	$pos = array_search($ch, $cho_list);
	if ($pos === false) {
		return -1;
	}
	return $pos;
}

function getJung($idx) {
	$jung_list = $GLOBALS["jung_list"];
	if($idx>=0 && $idx<count($jung_list)) {
		return $jung_list[$idx];
	}

	return false;
}

function getJungIdx($ch) {
	$jung_list = $GLOBALS["jung_list"];
	$pos = array_search($ch, $jung_list);
	if ($pos === false) {
		return -1;
	}
	return $pos;
}

function getJong($idx) {
	if($idx == 0) {
		return false;
	}
	$jong_list = $GLOBALS["jong_list"];

	if($idx>=0 && $idx<count($jong_list)) {
		return $jong_list[$idx];
	}

	return false;
}
function getJongIdx($ch) {
	if ($ch == 0) {
		return 0;
	}
	$jong_list = $GLOBALS["jong_list"];
	$ret = -1;

	$pos = array_search($ch, $jong_list);
	if ($pos === false) {
		return -1;
	}
	return $pos;
}


function combine($cho, $jung, $jong) {
	return (getChoIdx($cho) * 21 * 28 + getJungIdx($jung) * 28
			+ getJongIdx($jong) + 0xAC00);
}

function char_split($ch) {
	$cho = false;
	$jung = false;
	$jong = false;
	$x = ($ch & 0xFFFF); $y = 0; $z = 0;
	if ($x >= 0xAC00 && $x <= 0xD7A3) {
		$y = $x - 0xAC00;
		$z = $y % (21 * 28);
		$cho = getCho($y / (21 * 28));
		$jung = getJung($z / 28);
		$jong = getJong($z % 28);
		return array($cho, $jung, $jong);
	} else if ($x >= 0x3131 && $x <= 0x3163) {
		if (getChoIdx($ch) > -1) {
			$cho = $ch;
		} else if (getJungIdx($ch) > -1) {
			$jung = $ch;
		} else if (getJongIdx($ch) > -1) {
			$jong = $ch;
		}
		return array($cho, $jung, $jong);
	}
	return $ch;
}

function isSonor($mch) {
	$sonor = utf8_to_unicode("ㅏㅑㅓㅕㅗㅛㅜㅠㅡㅣ");
	$pos = array_search($mch, $sonor);
	return !($pos === false);
}


function HTR($prev_jong, $next_cho, $next_jung) {
	$next_first = utf8_to_unicode("ㄱㄴㄷㄹㅁㅂㅅㅈㅊㅋㅌㅍㅎㄲㄸㅃㅆㅉㅇ");
	$last_char = utf8_to_unicode("ㄱㄲㄳㄴㄵㄶㄷㄹㄺㄻㄼㄽㄾㄿㅀㅁㅂㅄㅅㅆㅇㅈㅊㅋㅌㅍㅎ");

	$array_chr = array( array( "кк","нн","кт","нън","нъм","кп","кс","кч","кчх","ккх","кх","кпх","кх","кк","ктт","кпп","ксс","кчч","г"),
		array( 	"кк","нн","кт","нън","нъм","кп","кс","кч","кчх","ккх","кх","кпх","кх","кк","ктт","кпп","ксс","кчч","г"),
		array( 	"кк","нн","кт","нън","нъм","кп","кс","кч","кчх","ккх","кх","кпх","кх","кк","ктт","кпп","ксс","кчч","г"),
		array( 	"нг","нн","нд","лл","нм","нб","нс","ндж","нчх","нкх","нтх","нпх","нх","нкк","нтт","нпп","нсс","нчч","н"),
		array( 	"нк","нн","нт","лл","нм","нп","нсс","нч","нчх","нкх","нтх","нпх","нчх","нкк","нтт","нпп","нсс","нчч","ндж"),
		array( 	"нкх","нн","нтх","лл","нм","нпх","нсс","нчх","нчх","нкх","нтх","нпх","нх","нкк","нтт","нпп","нсс","нчч","н"),
		array( 	"тк","нн","тт","нн","нм","тп","сс","тч","тчх","ткх","ттх","тпх","чх","ткк","тт","тпп","сс","чч","д"),
		array( 	"льг","лл","льт","лл","льм","льб","льс","льч","льчх","лькх","льтх","льпх","рх","лькк","льтт","льпп","льсс","льчч","р"),
		array( 	"г","нъ","кт","нън","нъм","кп","кс","кч","кчх","ккх","кх","кпх","кх","кк","ктт","кпп","ксс","кчч","льг"),
		array( 	"мг","мн","мд","мн","мм","мб","мс","мдж","мчх","мкх","мтх","мпх","мх","мкк","мтт","мпп","мсс","мчч","льм"),
		array( 	"льг","лл","льт","лл","льм","льб","льсс","льч","льчх","мкх","льтх","льпх","рх","лькк","льтт","льпп","льсс","льчч","льб"),
		array( 	"льк","лл","льт","лл","льм","льп","льсс","льч","льчх","лькх","льтх","льпх","рх","лькк","льтт","льпп","льсс","льчч","льс"),
		array( 	"льк","лл","льт","лл","льм","льп","льсс","льч","льчх","лькх","льтх","льпх","рх","лькк","льтт","льпп","льсс","льчч","льтх"),
		array( 	"пк","мн","пт","мн","мм","пп","пс","пч","пчх","пкх","птх","ппх","пх","пкк","птт","пп","псс","пчч","льпх"),
		array( 	"лькх","лл","льтх","лл","льм","льпх","льсс","льчх","льчх","лькх","льтх","льпх","рх","лькк","льтт","льпп","льсс","льчч","р"),
		array( 	"мг","мн","мд","мн","мм","мб","мс","мдж","мчх","мкх","мтх","мпх","мх","мкк","мтт","мпп","мсс","мчч","м"),
		array( 	"пк","мн","пт","мн","пм","пп","пс","пч","пчх","пкх","птх","ппх","пх","пкк","птт","пп","псс","пчч","б"),
		array( 	"пк","мн","пт","мн","пм","пп","пс","пч","пчх","пкх","птх","ппх","пх","пкк","птт","пп","псс","пчч","пс"),
		array( 	"тк","нн","тт","нн","нм","тп","сс","тч","тчх","ткх","ттх","тпх","тх","ткк","тт","тпп","сс","чч","с"),
		array( 	"тк","нн","тт","нн","нм","тп","сс","тч","тчх","ткх","ттх","тпх","тх","ткк","тт","тпп","сс","чч","сс"),
		array( 	"нъг","нън","нъд","лл","нъм","нъб","нъс[с]","нъдж","нъчх","нъкх","нътх","нъпх","нъх","нъкк","нътт","нъпп","нъсс","нъчч","нъ"),
		array( 	"тк","нн","тт","нн","нм","тп","сс","чч","ччх","ткх","ттх","тпх","тх","ткк","тт","тпп","сс","чч","дж"),
		array( 	"тк","нн","тт","нн","нм","тп","сс","чч","ччх","ткх","ттх","тпх","тх","ткк","тт","тпп","сс","чч","чх"),
		array( 	"кк","нън","кт","нън","нъм","кп","кс","кч","кчх","ккх","кх","кпх","кх","кк","ктт","кпп","ксс","кчч","кх"),
		array( 	"тк","нн","тт","нн","нм","тп","сс","тч","тчх","ткх","ттх","тпх","тх","ткк","тт","тпп","сс","чч","тх"),
		array( 	"пк","мн","пт","мн","пм","пп","пс","пч","пчх","пкх","птх","ппх","пх","пкк","птт","пп","псс","пчч","пх"),
		array( 	"тк","нн","тт","нн","нм","тп","сс","тч","тчх","ткх","ттх","тпх","тх","ткк","тт","тпп","сс","чч",""));


	$index_last = array_search($prev_jong, $last_char);
	if($index_last === false) {
		return "";
	}
	$index_next = array_search($next_cho, $next_first);

	if($index_next === false) {
		return "";
	}

	return $array_chr[$index_last][$index_next];
}

function HangulToRus($hangul) {
	$list_hangul = utf8_to_unicode("ㄱㄴㄷㄹㅁㅂㅅㅇㅈㅊㅋㅌㅍㅎㄲㄸㅃㅆㅉㅏㅑㅓㅕㅗㅛㅜㅠㅡㅣㅐㅒㅔㅖㅚㅟㅝㅙㅞㅢㅘㄳㄵㄶㄺㄻㄼㄽㄾㄿㅀㅄ");
	$list_first = array("к" , "н", "т", "р", "м", "п", "с", "н", "ч", "чх", "кх", "тх", "пх", "х", "кк", "тт", "пп", "сс", "чч", "а", "я", "о", "ё", "о", "ё", "у", "ю", "ы", "и", "э", "йя", "е", "йе", "ве", "ви", "во", "вэ", "ве", "ый", "ва", "", "", "", "", "", "","", "", "", "", "");
	$list_second = array("г","н","д","р","м","б","с","нъ","дж","чх","кх","тх","пх","х","кк","тт","пп","сс","чч", "а", "я", "о", "ё", "о", "ё", "у", "ю", "ы", "и", "э", "йя", "е", "е", "ве", "ви", "во", "вэ", "ве", "и", "ва", "", "", "", "", "", "","", "", "", "", "");
	$list_last = array("к", "н", "т", "ль", "м", "п", "т",
		"нъ", "т", "т", "к", "т", "п", "х", "кк", "тт", "пп", "т", "чч",
		"а", "я", "о", "ё", "о", "ё", "у", "ю", "ы", "и", "э", "йя", "е",
		"е", "ве", "ви", "во", "вэ", "ве", "ый", "ва", "к", "н", "н", "к", "м", "ль", "ль", "ль", "п","ль", "п");

	$ret_text = "";
	$b_skip = false;
	$b_first = true;
	$prev_jung = false;
	$prev_jong = false;
	$char_n = $list_hangul[7];

	$countchars = count($hangul);

	for ($i = 0; $i < $countchars; ++$i) {
	
		$uchar = char_split($hangul[$i]);
		
		if(!is_array($uchar)){
			$ret_text .= unicodechar_to_utf8($uchar);
			$prev_jung = false;
			$prev_jong = false;
			$b_skip = false;
			$b_first = true;
			continue;
		}
		$cho = $uchar[0];
		$jung = $uchar[1];
		$jong = $uchar[2];

		if($cho===false) {
			echo ("Error H01");
			break;
		}

		if(!$b_skip && $cho != $char_n) {
			$index = array_search($cho, $list_hangul);

			if($index === false) {
				echo "Error H02";
				break;
			}             

			if($b_first) {
				$ret_text .= $list_first[$index];
			} else {
				// значит предыдущая - гласная.
				$ret_text .= $list_second[$index];
			}             
		}
		$b_first = false;
		if($jung!==false) {
			$index = array_search($jung, $list_hangul);
			if($index === false) {
				echo("Error H03");
				break;
			}

			if($cho!=$char_n && $prev_jong===false && $prev_jung!==false && isSonor($prev_jung)){
				$ret_text.= $list_second[$index];
			}else{
				$ret_text .= $list_first[$index];
			}
		} else {
			$prev_jung = false;
			$prev_jong = false;
			$b_skip = false;

			$b_first = true; // ???????????

			continue;
		}
		if($jong !== false) {
			$b_last = true;
			if($i < $countchars - 1){
				$nch = 0;
				$ncho = false;
				$njung = false;
				$njong = false;

				$nch = char_split($hangul[$i+1]); // ncho, njung, njong
				if(is_array($nch)) {
					if($nch[0]===false ){
						echo("Error H05");
						break;
					}
					$tmp_text = HTR($jong, $nch[0], $nch[1]);
					$ret_text .=$tmp_text;
					$b_skip = true;
					$b_last = false;
				}
			}
			if($b_last) {
				$b_skip = false;
				$index = array_search($jong, $list_hangul);
				if($index === false) {
					echo("Error H04");
					break;
				}
				$ret_text .= $list_last[$index];
			}
		}else{
			$b_skip = false;
		}
		$prev_jung = $jung;
		$prev_jong = $jong;
         
	}
	return $ret_text;
}

$text = "";
if (isset($_GET['text'])) {
  $text = $_GET['text'];
  $text =  htmlspecialchars(substr($text, 0, 1000));
}

if(strlen($text) > 0) {
  $unitext = utf8_to_unicode($text);
  $result = HangulToRus($unitext);
  echo "'$result'";
  flog($text."($result)\n");
} else echo "none";
?>