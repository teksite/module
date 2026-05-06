<?php

namespace Teksite\Module\Console\Make\traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\Finder\Finder;

trait ReplaceStubGeneratorTrait
{
    /**
     * Cache for model lookups to avoid repeated filesystem scans.
     */
    private array $modelCache = [];
    private ?array $availableModelsCache = null;
    private ?array $possibleEventsCache = null;




    /**
     * Get possible events (with caching).
     */


    protected function possibleEvents(): array
    {
        if ($this->possibleEventsCache !== null) {
            return $this->possibleEventsCache;
        }

        $eventPath = $this->getModuleInput() === 'Steward'
            ? steward_path('app/Events')
            : module_path($this->getModuleInput(), 'app/Events');

        if (!is_dir($eventPath)) {
            $this->possibleEventsCache = [];
            return $this->possibleEventsCache;
        }

        $this->possibleEventsCache = (new Collection(Finder::create()->files()->depth(0)->in($eventPath)))
            ->map(fn($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();

        return $this->possibleEventsCache;
    }

    /**
     * Find available models (with caching).
     */
    protected function findAvailableModels(): array
    {
        if ($this->availableModelsCache !== null) {
            return $this->availableModelsCache;
        }

        $modelPath = $this->getModuleInput() === 'Steward'
            ? steward_path('App\\Models')
            : module_path($this->getModuleInput(), 'App\\Models');

        if (!is_dir($modelPath)) {
            $this->availableModelsCache = [];
            return $this->availableModelsCache;
        }

        $this->availableModelsCache = (new Collection(Finder::create()->files()->depth(0)->in($modelPath)))
            ->map(fn($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();

        return $this->availableModelsCache;
    }


    /**
     * Qualify model class name.
     */
    protected function qualifyModel(string $class, ?string $term = null, bool $check = false): ?string
    {
        $cacheKey = "{$class}|{$term}|{$check}";

        if (isset($this->modelCache[$cacheKey])) {
            return $this->modelCache[$cacheKey];
        }

        $modelNamespace = $this->guessModel($class, $term);

        if ($check && !class_exists($modelNamespace)) {
            $answer = $this->choice('The related model class does not exist. Do you want to continue?',
                [ 'yes', 'no', 'make' ],'yes');

            if ($answer === 'no') {
                $this->modelCache[$cacheKey] = null;
                return null;
            }
            if ($answer === 'make') {
                $this->call('model:make-model', ["name"=>$this->filename , 'module'=>$this->getModuleInput()]);
                return ;
            }
        }

        $this->modelCache[$cacheKey] = $modelNamespace;

        return $modelNamespace;
    }

    /**
     * Guess the model name.
     */
    protected function guessModel(string $name, ?string $term = null): string
    {
        $model = $this->extractModelName($name, $term);
        $model = normalizeSlashNamespace($model);

        return $this->applyModelNamespace($model);
    }

    /**
     * Extract model name from input.
     */
    private function extractModelName(string $name, ?string $term = null): string
    {
        if ($term && str_ends_with($name, $term)) {
            return substr($name, 0, -strlen($term));
        }

        return $name;
    }


    /**
     * Apply appropriate namespace to model.
     */
    private function applyModelNamespace(string $model): string
    {
        $stewardNamespace = steward_namespace();
        $rootStewardPattern = $stewardNamespace . '\\App\\Models\\';
        $stewardBasePattern = 'Steward\\App\\Models\\';
        $modulesAppModelsPattern = '/^([^\\\\s]+)\\\\App\\\\Modules\\\\(.+)$/';

        if (Str::startsWith($model, $rootStewardPattern)) {
            return $model;
        }

        if (Str::startsWith($model, $stewardBasePattern)) {
            $model = Str::replaceFirst($stewardBasePattern, '', $model);
            return 'Lareon\\' . $model;
        }

        if (preg_match('/^Lareon\\\\Modules\\\\([^\\\\s]+)\\\\App\\\\Modules\\\\(.+)$/', $model)) {
            return $model;
        }

        if (preg_match($modulesAppModelsPattern, $model)) {
            return 'Lareon\\Modules\\' . $model;
        }

        if (Str::startsWith($model, 'App\\Models\\')) {
            return $model;
        }

        return $this->getModuleInput() === 'Steward'
            ? steward_namespace() . '\\App\\Models\\' . $model
            : module_namespace($this->getModuleInput()) . '\\App\\Models\\' . $model;
    }

    /**
     * Get model replacements array.
     */
    protected function modelNameReplaces(): array
    {
        [$modelNamespace, $model] = $this->getModel();
        $modelVariable = lcfirst($model);

        return [
            '{{ model }}'           => $model,
            '{{model}}'             => $model,
            '{{ modelVariable }}'   => $modelVariable,
            '{{modelVariable}}'     => $modelVariable,
            '{{ namespacedModel }}' => $modelNamespace,
            '{{namespacedModel}}'   => $modelNamespace,
        ];
    }

    /**
     * Get model information.
     */
    protected function getModel(): array
    {
        $modelNamespace = $this->qualifyModel($this->option('model'));
        $model = class_basename($modelNamespace);

        return [$modelNamespace, $model];
    }

    /**
     * Get user model replacements.
     */
    protected function userNameReplaces(): array
    {
        $userModelNamespace = $this->userProviderModel();
        $userClassName = class_basename($userModelNamespace);

        return [
            '{{ namespacedUserModel }}' => $userModelNamespace,
            '{{namespacedUserModel}}'   => $userModelNamespace,
            '{{ user }}'                => $userClassName,
            '{{user}}'                  => $userClassName,
            '$user'                     => '$' . Str::camel($userClassName),
        ];
    }

    /**
     * Get the user provider model.
     */
    protected function userProviderModel(): ?string
    {
        $config = $this->laravel['config'];
        $guard = $this->hasOption('guard') ? $this->option('guard') : $config->get('auth.defaults.guard');

        $guardProvider = $config->get("auth.guards.{$guard}.provider");

        if (is_null($guardProvider)) {
            throw new LogicException("The [{$guard}] guard is not defined in your 'auth' configuration file.");
        }

        return $config->get("auth.providers.{$guardProvider}.model", 'App\\Models\\User');
    }


}
