<?php

namespace Hgraca\MicroOrm\DataSource;

interface ClientInterface
{
    public function executeQuery(string $queryString): array;

    public function executeCommand(string $queryString, array $bindingsList = []);
}
