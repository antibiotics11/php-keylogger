#!/usr/bin/env php
<?php

declare(ticks = 1, strict_types = 1);

const DEFAULT_DEVICE = "/dev/input/event4";
const DEFAULT_OUTPUT = "/dev/tty";

class input_event {
  public function __construct(
    public int|false $tv_sec  = 0, // unsigned long long
    public int|false $tv_usec = 0, // unsigned long long
    public int|false $type    = 0, // unsigned short
    public int|false $code    = 0, // unsigned short
    public int|false $value   = 0  // unsigned int
  ) {}
}

enum input_event_keys: int {
  case KEY_ESC        = 1;
  case KEY_1          = 2;
  case KEY_2          = 3;
  case KEY_3          = 4;
  case KEY_4          = 5;
  case KEY_5          = 6;
  case KEY_6          = 7;
  case KEY_7          = 8;
  case KEY_8          = 9;
  case KEY_9          = 10;
  case KEY_0          = 11;
  case KEY_MINUS      = 12;
  case KEY_EQUAL      = 13;
  case KEY_BACKSPACE  = 14;
  case KEY_TAB        = 15;
  case KEY_Q          = 16;
  case KEY_W          = 17;
  case KEY_E          = 18;
  case KEY_R          = 19;
  case KEY_T          = 20;
  case KEY_Y          = 21;
  case KEY_U          = 22;
  case KEY_I          = 23;
  case KEY_O          = 24;
  case KEY_P          = 25;
  case KEY_LEFTBRACE  = 26;
  case KEY_RIGHTBRACE = 27;
  case KEY_ENTER      = 28;
  case KEY_LEFTCTRL   = 29;
  case KEY_A          = 30;
  case KEY_S          = 31;
  case KEY_D          = 32;
  case KEY_F          = 33;
  case KEY_G          = 34;
  case KEY_H          = 35;
  case KEY_J          = 36;
  case KEY_K          = 37;
  case KEY_L          = 38;
  case KEY_SEMICOLON  = 39;
  case KEY_APOSTROPHE = 40;
  case KEY_GRAVE      = 41;
  case KEY_LEFTSHIFT  = 42;
  case KEY_BACKSLASH  = 43;
  case KEY_Z          = 44;
  case KEY_X          = 45;
  case KEY_C          = 46;
  case KEY_V          = 47;
  case KEY_B          = 48;
  case KEY_N          = 49;
  case KEY_M          = 50;
  case KEY_COMMA      = 51;
  case KEY_DOT        = 52;
  case KEY_SLASH      = 53;
  case KEY_RIGHTSHIFT = 54;
  case KEY_KPASTERISK = 55;
  case KEY_LEFTALT    = 56;
  case KEY_SPACE      = 57;
  case KEY_CAPSLOCK   = 58;
  case KEY_F1         = 59;
  case KEY_F2         = 60;
  case KEY_F3         = 61;
  case KEY_F4         = 62;
  case KEY_F5         = 63;
  case KEY_F6         = 64;
  case KEY_F7         = 65;
  case KEY_F8         = 66;
  case KEY_F9         = 67;
  case KEY_F10        = 68;
  case KEY_NUMLOCK    = 69;
  case KEY_SCROLLLOCK = 70;
  case KEY_F11        = 87;
  case KEY_F12        = 88;
  case KEY_RIGHTALT   = 100;
  case KEY_LINEFEED   = 101;
  case KEY_HOME       = 102;
  case KEY_UP         = 103;
  case KEY_PAGEUP     = 104;
  case KEY_LEFT       = 105;
  case KEY_RIGHT      = 106;
  case KEY_END        = 107;
  case KEY_DOWN       = 108;
  case KEY_PAGEDOWN   = 109;
  case KEY_INSERT     = 110;
  case KEY_DELETE     = 111;
  case KEY_MACRO      = 112;
  case KEY_MUTE       = 113;
  case KEY_VOLUMEDOWN = 114;
  case KEY_VOLUMEUP   = 115;
  case KEY_PAUSE      = 119;
}

function main(int $argc, array $argv): void {

  strcmp(PHP_OS, "Linux") === 0 or shutdown(1, "Must run on Linux.");
  posix_getuid() === 0 or shutdown(1, "Must run as root.");

  $argv = parse_argv($argc, $argv);
  $device = $argv["device"] ?? DEFAULT_DEVICE;
  $output = $argv["output"] ?? DEFAULT_OUTPUT;

  $read = @fopen($device, "r");
  $read === false and shutdown(1, "Failed to open device.");

  while (!feof($read)) {
    if (($event = fread($read, 24)) !== false) {
      $event = parse_event($event);

      if ($event->type !== 0x01) {
        continue;
      }

      $key = input_event_keys::tryFrom($event->code);
      $log = sprintf("[%d.%d]\t%s(%d)\r\n",
        $event->tv_sec,
        $event->tv_usec,
        $key->name      ?? "unknown",
        $key->value     ?? -1
      );

      @file_put_contents($output, $log, FILE_APPEND);

    }
  }

}

function parse_event(string $event): input_event|false {

  $unpacked = unpack("Qsec/Qusec/Stype/Scode/lvalue", $event);
  if ($unpacked === false) {
    return false;
  }

  return new input_event(
    $unpacked["sec"],
    $unpacked["usec"],
    $unpacked["type"],
    $unpacked["code"],
    $unpacked["value"]
  );

}

function parse_argv(int $argc, array $argv): array {

  $parsed_argv = [];
  for ($i = 0; $i < $argc; $i++) {
    $key = trim(strtolower($argv[$i]));
    if (in_array($key, [ "device", "output" ])) {
      $parsed_argv[$key] = $argv[++$i];
    }
  }

  return $parsed_argv;

}

function shutdown(int $code, string $message): int {
  printf("%s\r\n", $message);
  exit($code);
}

main($_SERVER["argc"], $_SERVER["argv"]);