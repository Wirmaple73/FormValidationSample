<?php
final class Header {
	private function __construct() { }
	
	public static function setLocation(string $url): void {
		header("Location: $url");
	}
}
