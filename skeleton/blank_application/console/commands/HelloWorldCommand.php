<?php
/**
 * @package console.{%PACKAGE_NAME%}
 */
class HelloWorldCommand extends Delta_ConsoleCommand
{
  public function execute()
  {
    $this->getOutput()->writeLine('Hello horld!');
  }
}
