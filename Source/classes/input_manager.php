<?php
require_once("assertion.php");

enum FormMethod {
	case Get;
	case Post;
}

readonly class InputInfo {
	private string $value;
	private string $displayName;
	
	private int $minLength;
	private int $maxLength;
	
	private ?string $regexPattern;
	private ?string $regexFailureMessage;
	
	public function getValue(): string {
		return $this->value;
	}
	
	public function getDisplayName(): string {
		return $this->displayName;
	}
	
	public function getMinLength(): int {
		return $this->minLength;
	}
	
	public function getMaxLength(): int {
		return $this->maxLength;
	}
	
	public function getRegexPattern(): ?string {
		return $this->regexPattern;
	}
	
	public function getRegexFailureMessage(): ?string {
		return $this->regexFailureMessage;
	}
	
	public function doesValueMatchRegexPattern(): bool {
		// Return true if the pattern is null, evaluate it otherwise
		return $this->regexPattern == null || preg_match($this->regexPattern, $this->value);
	}
	
	public function isValueLengthInRange(): bool {
		$length = mb_strlen($this->value);
		return $length >= $this->minLength && $length <= $this->maxLength;
	}
	
	public function __construct(string $value,
	                            string $displayName,
	                            int $minLength,
	                            int $maxLength,
	                            ?string $regexPattern = null,
	                            ?string $regexFailureMessage = null) {
		$this->value               = $value;
		$this->displayName         = $displayName;
		$this->minLength           = $minLength >= 1 ? $minLength : throw new InvalidArgumentException("Min length must be greater than zero.");
		$this->maxLength           = $maxLength >= 1 ? $maxLength : throw new InvalidArgumentException("Max length must be greater than zero.");
		$this->regexPattern        = $regexPattern;
		$this->regexFailureMessage = $regexFailureMessage;
	}
}

final class InputManager {
	private function __construct() { }
	
	public static function validateInput(InputInfo ...$inputInfoList): Assertion {
		$getValueNotInRangeMessage = function(InputInfo $info): string {
			return $info->getMinLength() === $info->getMaxLength() ?
				"{$info->getDisplayName()} must be {$info->getMinLength()} characters." :
				"{$info->getDisplayName()} must be between {$info->getMinLength()} and {$info->getMaxLength()} characters.";
		};
		
		foreach ($inputInfoList as $info) {
			$infoValue = $info->getValue();
			
			$result = Assertion::assertAll(
				new Assertion(isset($infoValue), "The form is missing certain required fields, please reload the page and try again."),
				new Assertion($info->isValueLengthInRange(), $getValueNotInRangeMessage($info)),
				new Assertion($info->doesValueMatchRegexPattern(), $info->getRegexFailureMessage())
			);
			
			if (!$result->isTrue())
				return $result;
		}
		
		return Assertion::getSuccessfulAssertion();
	}
	
	public static function areAllSet(FormMethod $method, string ...$names): bool {
		$requestArray = self::getRequestArray($method);
		
		foreach ($names as $name) {
			if (!isset($requestArray[$name]))
				return false;
		}
		
		return true;
	}
	
	public static function sanitizeAll(FormMethod $method, string ...$names): void {
		$stripExtraWhitespace = function(string $value): string {
			return preg_replace("/[\s ]+/", " ", $value);
		};
		
		// Can't use the 'getRequestArray' function below (request array has to be passed by reference)
		if ($method === FormMethod::Get)
			$requestArray = &$_GET;
		else
			$requestArray = &$_POST;
		
		foreach ($names as $name) {
			$requestArray[$name] = $stripExtraWhitespace(trim(filter_var($requestArray[$name], FILTER_SANITIZE_SPECIAL_CHARS)));
		}
	}
	
	private static function getRequestArray(FormMethod $method): array {
		return $method === FormMethod::Get ? $_GET : $_POST;
	}
}
