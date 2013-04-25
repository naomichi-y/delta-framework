<?php
/**
 * @package {%PACKAGE_TAG%}
 */
class {%COMMAND_NAME%}Command extends Delta_ConsoleCommand
{
  public function execute()
  {
    // Please write code here.
    $message = sprintf('Hello %s!', $this->getInput()->getCommandName());
    $this->getOutput()->writeLine($message);
  }
}
