<?php

namespace Teksite\Module\Console\Make\traits;

use Illuminate\Support\Collection;

trait ModuleValidationGeneratorTrait
{

    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'enum',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match',
        'namespace',
        'new',
        'or',
        'parent',
        'print',
        'private',
        'protected',
        'public',
        'readonly',
        'require',
        'require_once',
        'return',
        'self',
        'static',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        '__CLASS__',
        '__DIR__',
        '__FILE__',
        '__FUNCTION__',
        '__LINE__',
        '__METHOD__',
        '__NAMESPACE__',
        '__TRAIT__',
    ];

    /**
     * Checks whether the given name is reserved.
     *
     * @param string $module
     * @return bool
     */
    protected function isModuleExist(string $module): bool
    {
        $modules = get_modules_status(true);

        if (!in_array($module, array_keys($modules))) return false;
        if ($modules[$module] === false) $this->line("<fg=yellow;options=bold>{$module} in not active<>");
        return true;
    }

    /**
     * Checks whether the given name is reserved.
     *
     * @param string $name
     * @return bool
     */
    protected function isReservedName(string $name): bool
    {
        return in_array(
            strtolower($name),
            (new Collection($this->reservedNames))
                ->transform(fn($name) => strtolower($name))
                ->all()
        );
    }

    /**
     * handling rewrite if the final file exit or not,
     *
     * @param string $path
     * @return bool
     */
    protected function checkForce(string $path): bool
    {
        if ($this->alreadyExists($path)) {
            if ( !$this->option('force')){
                $this->components->error($this->type . ' already exists in ' . $path . '.');
                return false;
            }
        }
        return true;
    }




    /*
     *
     *
     *
     * */

    protected function validateModuleName(string $moduleName): array
    {
        // Exact match
        if ($this->exactMatch($moduleName)) {
            return [true, $moduleName];
        }
        // Check for similar matches
        if ($suggest =$this->getSimilarMatches($moduleName)) {
            return [false, $suggest];
        }

        return [false, null];
    }

    /**
     * Get all existing module names.
     *
     * @return array
     */
//    protected function getAllModuleNames(): array
//    {
//        $modulePath = base_path('Lareon/Modules');
//        if (!File::exists($modulePath)) {
//            return [];
//        }
//
//        return collect(File::directories($modulePath))
//            ->map(fn($path) => basename($path))
//            ->toArray();
//    }
//
//    /**
//     * Check if the given name matches any module name exactly.
//     *
//     * @param string $moduleName
//     * @return bool
//     */
//    protected function exactMatch(string $moduleName): bool
//    {
//        $allModules = $this->getAllModuleNames();
//
//        return in_array($moduleName, $allModules);
//    }
//
//    /**
//     * Check for similar matches in different cases and return suggestions.
//     *
//     * @param string $moduleName
//     * @return array
//     */
//    protected function getSimilarMatches(string $moduleName): ?string
//    {
//        $allModules = $this->getAllModuleNames();
//        return collect($allModules)->filter(function ($module) use ($moduleName) {
//            return strtolower($module) === strtolower($moduleName);
//        })->values()->first();
//    }
}
