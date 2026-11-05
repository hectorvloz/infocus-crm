<?php

use App\Http\Middleware\AuthenticateMcp;
use App\Mcp\Servers\InfocusServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth (claude.ai, ChatGPT): metadatos .well-known y registro dinámico de clientes.
Mcp::oauthRoutes();

// Servidor MCP. Acepta token personal (Configuración → MCP) o token OAuth. Solo administradores.
Mcp::web('/mcp', InfocusServer::class)
    ->middleware([AuthenticateMcp::class, 'throttle:mcp']);
