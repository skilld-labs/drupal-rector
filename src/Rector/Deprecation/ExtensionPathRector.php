<?php declare(strict_types=1);

namespace DrupalRector\Rector\Deprecation;

use DrupalRector\Rector\ValueObject\ExtensionPathConfiguration;
use DrupalRector\Services\AddCommentService;
use PhpParser\Node;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ExtensionPathRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var ExtensionPathConfiguration[]
     */
    private array $configuration;

    /**
     * @var \DrupalRector\Services\AddCommentService
     */
    private AddCommentService $commentService;

    public function __construct(AddCommentService $commentService) {
        $this->commentService = $commentService;
    }
    public function configure(array $configuration): void
    {
        foreach ($configuration as $value) {
            if (!($value instanceof ExtensionPathConfiguration)) {
                throw new \InvalidArgumentException(sprintf(
                    'Each configuration item must be an instance of "%s"',
                    ExtensionPathConfiguration::class
                ));
            }
        }

        $this->configuration = $configuration;
    }

    public function getNodeTypes(): array
    {
        return [
            Node\Stmt\Expression::class,
        ];
    }

    public function refactor(Node $node): ?Node
    {
        assert($node instanceof Node\Stmt\Expression);
        if (!($node->expr instanceof Node\Expr\FuncCall)) {
            return null;
        }

        foreach ($this->configuration as $configuration) {
            $expr = $node->expr;
            if ($this->getName($expr->name) !== $configuration->getFunctionName()) {
                continue;
            }
            $args = $expr->getArgs();
            if (count($args) !== 2) {
                $this->commentService->addDrupalRectorComment($node, "Invalid call to {$configuration->getFunctionName()}, cannot process.");
                return $node;
            }
            [$extensionType, $extensionName] = $args;

            $extensionTypeValueType = $this->nodeTypeResolver->getType($extensionType->value);
            if ($extensionTypeValueType->getConstantStrings()) {
                $extensionType = $extensionTypeValueType->getValue();
            }

            if (in_array($extensionType, [
                'module',
                'theme',
                'profile',
                'theme_engine'
            ], TRUE)) {
                $serviceName = "extension.list.$extensionType";
                $methodArgs = [$extensionName];
            }
            else {
                $this->commentService->addDrupalRectorComment(
                    $node,
                    'Unsupported extension type encountered, using extension.path.resolver instead of extension.list'
                );
                $serviceName = 'extension.path.resolver';
                $methodArgs = $args;
            }

            $service = new Node\Expr\StaticCall(
                new Node\Name\FullyQualified('Drupal'),
                'service',
                [new Node\Arg(new Node\Scalar\String_($serviceName))]
            );
            $methodName = new Node\Identifier($configuration->getMethodName());

            $node->expr = new Node\Expr\MethodCall($service, $methodName, $methodArgs);
            return $node;
        }

        return null;
    }

    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition('Fixes deprecated drupal_get_filename() calls', [
            new ConfiguredCodeSample(
                <<<'CODE_BEFORE'
drupal_get_filename('module', 'node');
drupal_get_filename('theme', 'seven');
drupal_get_filename('profile', 'standard');
CODE_BEFORE
                ,
                <<<'CODE_AFTER'
\Drupal::service('extension.list.module')->getPathname('node');
\Drupal::service('extension.list.theme')->getPathname('seven');
\Drupal::service('extension.list.profile')->getPathname('standard');
CODE_AFTER
                ,
                [
                    new ExtensionPathConfiguration('drupal_get_filename', 'getPathname'),
                ]
            ),
            new ConfiguredCodeSample(
                <<<'CODE_BEFORE'
drupal_get_path('module', 'node');
drupal_get_path('theme', 'seven');
drupal_get_path('profile', 'standard');
CODE_BEFORE
                ,
                <<<'CODE_AFTER'
\Drupal::service('extension.list.module')->getPath('node');
\Drupal::service('extension.list.theme')->getPath('seven');
\Drupal::service('extension.list.profile')->getPath('standard');
CODE_AFTER
                ,
                [
                    new ExtensionPathConfiguration('drupal_get_path', 'getPath'),
                ]
            )
        ]);
    }

}
