<?php

namespace Phpactor\Extension\LanguageServerCompletion\Tests\Integration;

use Closure;
use Generator;
use Phpactor\Extension\LanguageServerCompletion\Tests\IntegrationTestCase;
use Phpactor\ObjectRenderer\Model\ObjectRenderer;
use Phpactor\ObjectRenderer\ObjectRendererBuilder;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\Core\SourceCodeLocator\TemporarySourceLocator;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;
use RuntimeException;

class MarkdownObjectRendererTest extends IntegrationTestCase
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ObjectRenderer
     */
    private $renderer;

    /**
     * @var TemporarySourceLocator
     */
    private $locator;

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->mkdir('project');
        $this->locator = new StubSourceLocator(ReflectorBuilder::create()->build(), $this->workspace()->path('project'), $this->workspace()->path('cache'));
        $this->reflector = ReflectorBuilder::create()
            ->addLocator($this->locator)
            ->enableContextualSourceLocation()
            ->build();
        $this->renderer = ObjectRendererBuilder::create()
             ->addTemplatePath(__DIR__ .'/../../../templates/markdown')
             ->enableInterfaceCandidates()
             ->build();
    }

    /**
     * @dataProvider provideRender
     */
    public function testRender(string $manifest, Closure $objectFactory, string $expected, bool $capture = false): void
    {
        $this->workspace()->loadManifest($manifest);

        $object = $objectFactory($this->reflector);
        $path = __DIR__ . '/expected/'. $expected;

        if (!file_exists($path)) {
            throw new RuntimeException(sprintf(
                'Expected template does not exist at "%s"', $path
            ));
        }

        $actual = $this->renderer->render($object);

        if ($capture) {
            fwrite(STDOUT, sprintf("\nCaptured %s\n", $path));
            file_put_contents($path, $actual);
        }


        self::assertEquals(file_get_contents($path), $actual);
    }

    /**
     * @return Generator<array>
     */
    public function provideRender(): Generator
    {
        yield 'simple object' => [
            '',
            function (Reflector $reflector) {
                return $reflector->reflectClassesIn('<?php class Foobar {}')->first();
            },
            'class_reflection1.md'
        ];

        yield 'complex object' => [
            '',
            function (Reflector $reflector) {
                return $reflector->reflectClassesIn(<<<'EOT'
<?php

interface DoesThis
{
}
interface DoesThat
{
}
abstract class SomeAbstract
{
}

/**
 * This is my class, my there are many like it, but this one is mine.
 */
class Concrete extends SomeAbstract implements DoesThis, DoesThat
{
    public function __construct(string $foo) {}
    public function foobar(): SomeAbstract;
}
EOT
            )->get('Concrete');

            },
            'class_reflection2.md',
            //true
        ];
    }
}
