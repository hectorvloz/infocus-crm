<?php

namespace App\Mcp\Support;

use RuntimeException;

/** Acción rechazada por permisos o nivel de acceso de la conexión MCP. */
class McpDenied extends RuntimeException
{
}
