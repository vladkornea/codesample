<?php

interface SesInterface {
	function sendRawEmail (string $raw_message): array;
} // SesInterface

class SES extends SESv4 implements SesInterface {};
