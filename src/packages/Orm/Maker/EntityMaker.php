<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Maker;

use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Exception\OrmException;
use ReflectionMethod;

class EntityMaker extends AbstractMaker
{
    public const TYPES = [
        'string' => ['?string', null],
        'text' => ['?string', 'text'],
        'integer' => ['?int', null],
        'int' => ['?int', null],
        'smallint' => ['?int', 'smallint'],
        'bigint' => ['?int', 'bigint'],
        'float' => ['?float', null],
        'decimal' => ['?string', 'decimal'],
        'boolean' => ['?bool', null],
        'bool' => ['?bool', null],
        'datetime' => ['?\DateTime', null],
        'datetime_immutable' => ['?\DateTimeImmutable', null],
        'date' => ['?\DateTime', 'date'],
        'date_immutable' => ['?\DateTimeImmutable', 'date_immutable'],
        'time' => ['?\DateTime', 'time'],
        'time_immutable' => ['?\DateTimeImmutable', 'time_immutable'],
        'json' => ['?array', null],
        'guid' => ['?string', 'guid'],
        'uuid' => ['?string', 'guid'],
    ];

    public const RELATIONS = ['manytoone' => 'ManyToOne', 'onetoone' => 'OneToOne', 'onetomany' => 'OneToMany', 'manytomany' => 'ManyToMany'];

    protected array $uses = [];

    public function parseFields(array $specs): array
    {
        $fields = [];

        foreach ($specs as $spec) {
            $spec = trim((string) $spec);
            $nullable = str_ends_with($spec, '?');
            $parts = explode(':', rtrim($spec, '?'));
            $name = array_shift($parts);
            $type = strtolower($parts[0] ?? 'string');

            if (preg_match('/^[a-z][A-Za-z0-9_]*$/', $name) !== 1) {
                throw new OrmException('The field name "{name}" is not valid: use camelCase.', 0, null, ['name' => $name]);
            }

            if (isset(self::RELATIONS[$type])) {
                if (!isset($parts[1]) || $parts[1] === '') {
                    throw new OrmException('The relation "{spec}" needs a target entity: {name}:{type}:Target.', 0, null, ['spec' => $spec, 'name' => $name, 'type' => self::RELATIONS[$type]]);
                }

                $fields[$name] = ['name' => $name, 'relation' => self::RELATIONS[$type], 'target' => $parts[1], 'mappedBy' => $parts[2] ?? null, 'nullable' => $nullable];
                continue;
            }

            if ($type === 'enum') {
                if (!isset($parts[1]) || $parts[1] === '') {
                    throw new OrmException('The enum field "{spec}" needs a class: {name}:enum:App\\Enum\\Status.', 0, null, ['spec' => $spec, 'name' => $name]);
                }

                $fields[$name] = ['name' => $name, 'type' => 'enum', 'enum' => ltrim($parts[1], '\\'), 'nullable' => $nullable, 'length' => null];
                continue;
            }

            if (!isset(self::TYPES[$type])) {
                throw new OrmException('Unknown type "{type}" for the field "{name}". Types: {types}, enum:Class, ManyToOne:Target, OneToOne:Target, OneToMany:Target[:mappedBy], ManyToMany:Target.', 0, null, [
                    'type' => $type,
                    'name' => $name,
                    'types' => implode(', ', array_keys(self::TYPES)),
                ]);
            }

            $fields[$name] = [
                'name' => $name,
                'type' => $type,
                'nullable' => $nullable,
                'length' => $type === 'string' && isset($parts[1]) ? (int) $parts[1] : null,
                'precision' => $type === 'decimal' ? (int) ($parts[1] ?? 10) : null,
                'scale' => $type === 'decimal' ? (int) ($parts[2] ?? 2) : null,
            ];
        }

        return $fields;
    }

    public function make(string $name, array $fields = [], ?string $repositoryClass = null, bool $force = false): array
    {
        [$class, $file] = $this->resolve($name);
        $namespace = self::namespaceOf($class);
        $short = self::shortName($class);
        $this->uses = [];

        if ($repositoryClass !== null) {
            $this->uses[$repositoryClass] = true;
        }

        $this->uses['NeoPHP\\Package\\Orm\\Mapping as ORM'] = true;
        $properties = ["    #[ORM\\Id]\n    #[ORM\\GeneratedValue]\n    #[ORM\\Column]\n    private ?int \$id = null;"];
        $methods = ["    public function getId(): ?int\n    {\n        return \$this->id;\n    }"];
        $constructor = [];

        foreach ($fields as $field) {
            if (isset($field['relation'])) {
                $this->relation($field, $short, $namespace, $properties, $methods, $constructor);
            } else {
                $this->field($field, $properties, $methods);
            }
        }

        $uses = array_keys($this->uses);
        sort($uses);
        $entity = $repositoryClass !== null ? '#[ORM\\Entity(repository: ' . self::shortName($repositoryClass) . '::class)]' : '#[ORM\\Entity]';
        $body = implode("\n\n", $properties);

        if ($constructor !== []) {
            $body .= "\n\n    public function __construct()\n    {\n" . implode("\n", $constructor) . "\n    }";
        }

        $body .= "\n\n" . implode("\n\n", $methods);

        $code = '<?php' . "\n\n"
            . 'declare(strict_types=1);' . "\n\n"
            . 'namespace ' . $namespace . ';' . "\n\n"
            . implode("\n", array_map(static fn (string $use): string => 'use ' . $use . ';', $uses)) . "\n\n"
            . $entity . "\n"
            . 'class ' . $short . "\n"
            . "{\n"
            . $body . "\n"
            . "}\n";

        $this->write($file, $code, $force);

        return [$class, $file];
    }

    protected function field(array $field, array &$properties, array &$methods): void
    {
        $name = $field['name'];

        if ($field['type'] === 'enum') {
            $this->uses[$field['enum']] = true;
            $phpType = '?' . self::shortName($field['enum']);
            $options = $field['nullable'] ? ['nullable: true'] : [];
        } else {
            [$phpType, $columnType] = self::TYPES[$field['type']];

            if (str_contains($phpType, '\\')) {
                $classType = ltrim(substr($phpType, 1), '\\');
                $this->uses[$classType] = true;
                $phpType = '?' . $classType;
            }

            $options = [];

            if ($columnType !== null) {
                $options[] = "type: '" . $columnType . "'";
            }

            if ($field['length'] !== null && $field['length'] !== 255) {
                $options[] = 'length: ' . $field['length'];
            }

            if ($field['type'] === 'decimal') {
                $options[] = 'precision: ' . $field['precision'];
                $options[] = 'scale: ' . $field['scale'];
            }

            if ($field['nullable']) {
                $options[] = 'nullable: true';
            }
        }

        $properties[] = '    #[ORM\\Column' . ($options === [] ? '' : '(' . implode(', ', $options) . ')') . "]\n    private " . $phpType . ' $' . $name . ' = null;';
        $studly = ucfirst($name);
        $valueType = $field['nullable'] ? $phpType : ltrim($phpType, '?');
        $getter = $field['type'] === 'boolean' || $field['type'] === 'bool' ? 'is' . $studly : 'get' . $studly;
        $methods[] = '    public function ' . $getter . '(): ' . $phpType . "\n    {\n        return \$this->" . $name . ";\n    }";
        $methods[] = '    public function set' . $studly . '(' . $valueType . ' $' . $name . "): static\n    {\n        \$this->" . $name . ' = $' . $name . ";\n\n        return \$this;\n    }";
    }

    protected function relation(array $field, string $owner, string $namespace, array &$properties, array &$methods, array &$constructor): void
    {
        $name = $field['name'];
        $target = str_contains($field['target'], '\\') ? ltrim($field['target'], '\\') : $namespace . '\\' . $field['target'];
        $targetShort = self::shortName($target);
        $studly = ucfirst($name);

        if (self::namespaceOf($target) !== $namespace) {
            $this->uses[$target] = true;
        }

        if (in_array($field['relation'], ['ManyToOne', 'OneToOne'], true)) {
            $options = [$targetShort . '::class'];

            if (!$field['nullable']) {
                $options[] = 'nullable: false';
            }

            $properties[] = '    #[ORM\\' . $field['relation'] . '(' . implode(', ', $options) . ")]\n    private ?" . $targetShort . ' $' . $name . ' = null;';
            $methods[] = '    public function get' . $studly . '(): ?' . $targetShort . "\n    {\n        return \$this->" . $name . ";\n    }";
            $methods[] = '    public function set' . $studly . '(' . ($field['nullable'] ? '?' : '') . $targetShort . ' $' . $name . "): static\n    {\n        \$this->" . $name . ' = $' . $name . ";\n\n        return \$this;\n    }";

            return;
        }

        $this->uses[CollectionInterface::class] = true;
        $this->uses[ArrayCollection::class] = true;
        $singular = self::singularize($name);
        $singularStudly = ucfirst($singular);
        $constructor[] = '        $this->' . $name . ' = new ArrayCollection();';

        if ($field['relation'] === 'OneToMany') {
            $mappedBy = $field['mappedBy'] ?? lcfirst($owner);
            $properties[] = '    #[ORM\\OneToMany(' . $targetShort . "::class, mappedBy: '" . $mappedBy . "')]\n    private CollectionInterface \$" . $name . ';';
            $methods[] = '    public function get' . $studly . "(): CollectionInterface\n    {\n        return \$this->" . $name . ";\n    }";
            $methods[] = '    public function add' . $singularStudly . '(' . $targetShort . ' $' . $singular . "): static\n    {\n        if (!\$this->" . $name . '->contains($' . $singular . ")) {\n            \$this->" . $name . '->add($' . $singular . ");\n            \$" . $singular . '->set' . ucfirst($mappedBy) . "(\$this);\n        }\n\n        return \$this;\n    }";
            $methods[] = self::acceptsNull($target, 'set' . ucfirst($mappedBy))
                ? '    public function remove' . $singularStudly . '(' . $targetShort . ' $' . $singular . "): static\n    {\n        if (\$this->" . $name . '->removeElement($' . $singular . ') && $' . $singular . '->get' . ucfirst($mappedBy) . "() === \$this) {\n            \$" . $singular . '->set' . ucfirst($mappedBy) . "(null);\n        }\n\n        return \$this;\n    }"
                : '    public function remove' . $singularStudly . '(' . $targetShort . ' $' . $singular . "): static\n    {\n        \$this->" . $name . '->removeElement($' . $singular . ");\n\n        return \$this;\n    }";

            return;
        }

        $options = [$targetShort . '::class'];

        if ($field['mappedBy'] !== null) {
            $options[] = "mappedBy: '" . $field['mappedBy'] . "'";
        }

        $properties[] = '    #[ORM\\ManyToMany(' . implode(', ', $options) . ")]\n    private CollectionInterface \$" . $name . ';';
        $methods[] = '    public function get' . $studly . "(): CollectionInterface\n    {\n        return \$this->" . $name . ";\n    }";
        $methods[] = '    public function add' . $singularStudly . '(' . $targetShort . ' $' . $singular . "): static\n    {\n        if (!\$this->" . $name . '->contains($' . $singular . ")) {\n            \$this->" . $name . '->add($' . $singular . ");\n        }\n\n        return \$this;\n    }";
        $methods[] = '    public function remove' . $singularStudly . '(' . $targetShort . ' $' . $singular . "): static\n    {\n        \$this->" . $name . '->removeElement($' . $singular . ");\n\n        return \$this;\n    }";
    }

    public static function acceptsNull(string $class, string $method): bool
    {
        if (!class_exists($class) || !method_exists($class, $method)) {
            return false;
        }

        $parameters = (new ReflectionMethod($class, $method))->getParameters();

        return isset($parameters[0]) && $parameters[0]->allowsNull();
    }

    public static function singularize(string $name): string
    {
        return match (true) {
            str_ends_with($name, 'ies') => substr($name, 0, -3) . 'y',
            str_ends_with($name, 'sses'), str_ends_with($name, 'xes'), str_ends_with($name, 'ches'), str_ends_with($name, 'shes') => substr($name, 0, -2),
            str_ends_with($name, 's') && !str_ends_with($name, 'ss') => substr($name, 0, -1),
            default => $name . 'Item',
        };
    }
}