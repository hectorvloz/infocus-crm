<?php

use App\Http\Middleware\AuthenticateMcp;
use App\Mcp\Servers\InfocusServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth (claude.ai, ChatGPT): metadatos .well-known y registro dinámico de clientes.
Mcp::oauthRoutes();
\Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();
\Illuminate\Support\Facades\Route::getRoutes()->getByName('mcp.oauth.register')
    ?->middleware(\App\Http\Middleware\LogRejectedOAuthRegistration::class);

// Servidor MCP. Acepta token personal (Configuración → MCP) o token OAuth. Solo administradores.
Mcp::web('/mcp', InfocusServer::class)
    ->middleware([AuthenticateMcp::class, 'throttle:mcp']);
