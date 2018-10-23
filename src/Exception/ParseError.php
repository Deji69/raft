<?php
namespace Raft\Exception;

use Exception;

/**
 * ParseError is thrown when a PHTML syntax is not valid.
 */
class ParseError extends Exception implements ExceptionInterface
{
}
