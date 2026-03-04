<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Rules\Rule;

use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    // Правило: Domain не должен зависеть от других слоев (Application, Infrastructure, Controller)
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new NotDependsOnTheseNamespaces('App\Application', 'App\Infrastructure', 'App\Controller'))
        ->since('п.5 REPAIR.md')
        ->because('слой Domain должен быть независимым и содержать только бизнес-логику');

    // Правило: Application может зависеть от Domain, но не от Infrastructure/Controller
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Application'))
        ->should(new NotDependsOnTheseNamespaces('App\Infrastructure', 'App\Controller'))
        ->since('п.5 REPAIR.md')
        ->because('слой Application координирует работу, но не должен зависеть от деталей реализации инфраструктуры');

    $config->add($classSet, ...$rules);
};
