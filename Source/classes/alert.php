<?php
final class Alert {
	private function __construct() { }
	
	public static function display(string $message): void {
		echo("<script>alert('$message');</script>");
	}
	
	public static function displayAndExit(string $message): void {
		self::display($message);
		exit(/* $message */);
	}
}
