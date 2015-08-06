<?php

/**
 * @package DataTypes
 */

class DataType_PersonalNumber extends DataTypePlugin {
	protected $isEnabled = true;
	protected $dataTypeName = "PersonalNumber";
	protected $dataTypeFieldGroup = "human_data";
	protected $dataTypeFieldGroupOrder = 110;
	protected $jsModules = array("PersonalNumber.js");
	private $generatedPersonnrs = array();
	// Separator in personal number
	// static $sep = "-";


	/**
	 * Generate a random personal number, and return the display string and additional meta data for use
	 * by any other Data Type.
	 */
	public function generate($generator, $generationContextData) {
		$generationOptions = $generationContextData["generationOptions"];
		
		// Default, 12 siffers + '-'
		// TODO: Option for 12 siffers without '-'
		// TODO: more options? (not 10 siffers since it could generate real personal number)
		// TODO: support several countries?
		$ccSeparator = '-'; //self::getPersonalNumberSeparator($options["cc_separator"]);

		$personnr = $this->generateRandomSwedishPersonalNumber($ccSeparator);

		// pretty sodding unlikely, but just in case!
		while (in_array($personnr, $this->generatedPersonnrs)) {
			$personnr = $this->generateRandomSwedishPersonalNumber($ccSeparator);
		}
		$this->generatedPersonnrs[] = $personnr;
		return array(
			"display" => $personnr
		);
	}
	
	// TODO: add support for separator
	// TODO: add support for organisation numbers
	private static function generateRandomSwedishPersonalNumber($sep) {
		$new_str = "16";
		$rand = 0;

		$cnt = 13;	// 12 siffers + 1 increment for separator
		
		for ($i=2; $i<$cnt; $i++) {
			switch ($i) {
				case 2:
					$rand = mt_rand(0, 99);
					$new_str .= sprintf("%02d", $rand);
					break;
				case 4: 
					$rand = mt_rand(1, 12);
					$new_str .= sprintf("%02d", $rand);
					break;
				case 6:
					$rand = mt_rand(1, 30);
					$new_str .= sprintf("%02d", $rand);
					break;
				case 8: 
					//$new_str .= $self->sep;
					$new_str .= $sep;
					break;
				case 9: 
					$new_str .= "101";
					break;
				case 12:
					$ctrl = self::recalcCtrl($new_str . "0", "-");
					$new_str .= sprintf("%01d", $ctrl);
					break;
				default:
					break;
			}
		}

		return $new_str;
	}
	
	// Function to recalculate control siffer in swedish personal number
	private static function recalcCtrl($idNumber, $separator) {
		$strArr = explode($separator, $idNumber);
		$idNr = "";
		for($i=0; $i<count($strArr); $i++)
			$idNr .= $strArr[$i];
		
		$idNrArr = str_split($idNr);
		
		$ctrl = 0;
		
		// Ogiltig längd
		if(!((strlen($idNr) == 12) || (strlen($idNr) == 10)))
			return 99;
		
		// OK, 12 siffers (person) or 10 siffers (organisation), recalculate control siffer
		$partSum=0;
		$sum=0;
		
		for($i = strlen($idNr) - 10; $i< strlen($idNr) - 1; $i++)
		{
			if($i%2 == 0)
			{
				$siffra = intval($idNrArr[$i]);
				$partSum = $siffra * 2;
				if($partSum >= 10)
					$partSum = (int)($partSum / 10) + ($partSum % 10);
			}
			else
				$partSum = intval($idNrArr[$i]);
			
			$sum+= $partSum;
		}
		
		$ctrl = (10 - ($sum % 10)) % 10;
		
		return $ctrl;
	}


	private static function getPersonalNumberSeparator($separators) {
		$separatorList = explode("|", $separators);
		$chosenSep = $separatorList[rand(0, count($separatorList)-1)];

		// if no separator was entered use '' as default
		if ($separators == "") {
			$chosenSep = "";
		}
		return $chosenSep;
	}

	public function getExampleColumnHTML() {
		$L = Core::$language->getCurrentLanguageStrings();

		$html =<<< END
	<select name="dtExample_%ROW%" id="dtExample_%ROW%">
		<option value="">{$L["please_select"]}</option>
		<option value="PersonalNumberWithoutHyphen">{$this->L["example_PersonalNumberWithoutHyphen"]}</option>
		<option value="PersonalNumberWithHyphen">{$this->L["example_PersonalNumberWithHyphen"]}</option>
	</select>
END;
		return $html;
	}

	public function getOptionsColumnHTML() {
		$html =<<< END
<span id="dtOptionPersonalNumberSeparator_%ROW%" style="display:inline;">
	{$this->L["separators"]}
	<input type="text" name="dtOptionPersonalNumber_sep_%ROW%" id="dtOptionPersonalNumber_sep_%ROW%" style="width: 78px" value=" " title="{$this->L["separator_help"]}" />
</span>
END;
		return $html;
//		return '<input type="text" name="dtOption_%ROW%" id="dtOption_%ROW%" style="width: 267px" />';
	}

	/*public function getRowGenerationOptionsUI($generator, $postdata, $colNum, $numCols) {
		if (!isset($post["dtOption_$colNum"]) || empty($post["dtOption_$colNum"])) {
			return false;
		}
		return $post["dtOption_$colNum"];
	}*/
	public function getRowGenerationOptionsUI($generator, $postdata, $colNum, $numCols) {
		return array(
			"cc_separator"   => $postdata["dtOptionPersonalNumber_sep_$colNum"],
			"cc_format"      => $postdata["dtOption_$colNum"],
		);
	}

	public function getHelpHTML() {
		$content =<<<EOF
	<p>
		{$this->L["help_text"]}
	</p>

	<table cellpadding="0" cellspacing="1">
	<tr>
		<td width="100"><h4>PersonalNumberWithoutHyphen</h4></td>
		<td>{$this->L["type_PersonalNumberWithoutHyphen"]}</td>
	</tr>
	<tr>
		<td><h4>PersonalNumberWithHyphen</h4></td>
		<td>{$this->L["type_PersonalNumberWithHyphen"]}</td>
	</tr>
	</table>
EOF;

		return $content;
	}

	public function getDataTypeMetadata() {
		$len = 12 + strlen($self->sep);
		return array(
			"SQLField" => "varchar(" . $len . ") default NULL",
			"SQLField_Oracle" => "varchar2(" . $len . ") default NULL",
			"SQLField_MSSQL" => "VARCHAR(" . $len . ") NULL"
		);
	}

}
