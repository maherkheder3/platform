<?php declare(strict_types=1);

namespace Shopware\Core\Content\Test\Media\Commands;

use Doctrine\DBAL\Connection;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Memory\MemoryAdapter;
use Shopware\Core\Content\Media\Commands\GenerateThumbnailsCommand;
use Shopware\Core\Content\Media\MediaStruct;
use Shopware\Core\Content\Media\Thumbnail\ThumbnailConfiguration;
use Shopware\Core\Content\Media\Thumbnail\ThumbnailService;
use Shopware\Core\Content\Media\Thumbnail\ThumbnailStruct;
use Shopware\Core\Content\Media\Util\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\Struct\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class GenerateThumbnailsCommandTest extends KernelTestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var GenerateThumbnailsCommand
     */
    private $thumbnailCommand;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ThumbnailService */
    private $thumbnailService;

    /** @var ThumbnailConfiguration */
    private $thumbnailConfiguration;

    /** @var Context */
    private $context;

    /** @var string */
    private $catalogId;

    public function setUp()
    {
        self::bootKernel();
        $this->repository = self::$container->get('media.repository');
        $this->connection = self::$container->get(Connection::class);
        $this->filesystem = new Filesystem(new MemoryAdapter());
        $this->urlGenerator = self::$container->get(UrlGeneratorInterface::class);
        $this->thumbnailConfiguration = self::$container->get(ThumbnailConfiguration::class);
        $this->context = Context::createDefaultContext(Defaults::TENANT_ID);
        $this->thumbnailService = new ThumbnailService($this->repository, $this->filesystem, $this->urlGenerator, $this->thumbnailConfiguration);

        $this->thumbnailCommand = new GenerateThumbnailsCommand(
            $this->thumbnailService,
            $this->repository
        );

        $this->connection->beginTransaction();
        $this->createNewCatalog();
        $this->context->getExtension('write_protection')->set('write_media', true);
    }

    public function tearDown(): void
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testExecuteHappyPath()
    {
        $this->createValidMediaFiles();

        $input = new StringInput(sprintf('-t %s -c %s', Defaults::TENANT_ID, $this->catalogId));
        $output = new BufferedOutput();

        $this->thumbnailCommand->run($input, $output);

        $string = $output->fetch();
        $this->assertEquals(1, preg_match('/.*Generated\s*2.*/', $string));
        $this->assertEquals(1, preg_match('/.*Skipped\s*0.*/', $string));

        $expectedNumberOfThumbnails = count($this->thumbnailConfiguration->getThumbnailSizes());
        if ($this->thumbnailConfiguration->isHighDpi()) {
            $expectedNumberOfThumbnails *= 2;
        }

        $searchCriteria = new Criteria();
        $mediaResult = $this->repository->search($searchCriteria, $this->context);
        /** @var MediaStruct $updatedMedia */
        foreach ($mediaResult->getEntities() as $updatedMedia) {
            $thumbnails = $updatedMedia->getThumbnails();
            static::assertEquals(
                $expectedNumberOfThumbnails,
                $thumbnails->count()
            );

            foreach ($thumbnails as $thumbnail) {
                $this->assertThumbnailExists($updatedMedia->getId(), $updatedMedia->getMimeType(), $thumbnail);
            }
        }
    }

    public function testItSkipsNotSupportedMediaTypes()
    {
        $this->createNotSupportedMediaFiles();

        $input = new StringInput(sprintf('-t %s -c %s', Defaults::TENANT_ID, $this->catalogId));
        $output = new BufferedOutput();

        $this->thumbnailCommand->run($input, $output);

        $string = $output->fetch();
        $this->assertEquals(1, preg_match('/.*Generated\s*1.*/', $string));
        $this->assertEquals(1, preg_match('/.*Skipped\s*1.*/', $string));

        $expectedNumberOfThumbnails = count($this->thumbnailConfiguration->getThumbnailSizes());
        if ($this->thumbnailConfiguration->isHighDpi()) {
            $expectedNumberOfThumbnails *= 2;
        }

        $searchCriteria = new Criteria();
        $mediaResult = $this->repository->search($searchCriteria, $this->context);
        /** @var MediaStruct $updatedMedia */
        foreach ($mediaResult->getEntities() as $updatedMedia) {
            if (substr($updatedMedia->getMimeType(), 0, 6) === 'image') {
                $thumbnails = $updatedMedia->getThumbnails();
                static::assertEquals(
                    $expectedNumberOfThumbnails,
                    $thumbnails->count()
                );

                foreach ($thumbnails as $thumbnail) {
                    $this->assertThumbnailExists($updatedMedia->getId(), $updatedMedia->getMimeType(), $thumbnail);
                }
            }
        }
    }

    protected function assertThumbnailExists(string $mediaId, string $mimeType, ThumbnailStruct $thumbnail): void
    {
        $thumbnailPath = $this->urlGenerator->getThumbnailUrl(
            $mediaId,
            $mimeType,
            $thumbnail->getWidth(),
            $thumbnail->getHeight(),
            false,
            false);
        static::assertTrue($this->filesystem->has($thumbnailPath));

        if ($thumbnail->isHighDpi()) {
            $thumbnailPath = $this->urlGenerator->getThumbnailUrl(
                $mediaId,
                $mimeType,
                $thumbnail->getWidth(),
                $thumbnail->getHeight(),
                true,
                false);
            static::assertTrue($this->filesystem->has($thumbnailPath));
        }
    }

    protected function createValidMediaFiles(): void
    {
        $media = [
            'id' => Uuid::uuid4()->getHex(),
            'name' => 'test_media',
            'mimeType' => 'image/png',
            'catalogId' => $this->catalogId,
        ];

        $this->repository->create([$media], $this->context);
        $filePath = $this->urlGenerator->getMediaUrl($media['id'], 'image/png', false);
        $this->filesystem->putStream($filePath, fopen(__DIR__ . '/../fixtures/shopware-logo.png', 'r'));

        $media = [
            'id' => Uuid::uuid4()->getHex(),
            'name' => 'test_media2',
            'mimeType' => 'image/jpg',
            'catalogId' => $this->catalogId,
        ];

        $this->repository->create([$media], $this->context);
        $filePath = $this->urlGenerator->getMediaUrl($media['id'], 'image/jpg', false);
        $this->filesystem->putStream($filePath, fopen(__DIR__ . '/../fixtures/shopware.jpg', 'r'));
    }

    protected function createNotSupportedMediaFiles(): void
    {
        $media = [
            'id' => Uuid::uuid4()->getHex(),
            'name' => 'test_media',
            'mimeType' => 'application/pdf',
            'catalogId' => $this->catalogId,
        ];

        $this->repository->create([$media], $this->context);
        $filePath = $this->urlGenerator->getMediaUrl($media['id'], 'application/pdf', false);
        $this->filesystem->putStream($filePath, fopen(__DIR__ . '/../fixtures/Shopware_5_3_Broschuere.pdf', 'r'));

        $media = [
            'id' => Uuid::uuid4()->getHex(),
            'name' => 'test_media2',
            'mimeType' => 'image/jpg',
            'catalogId' => $this->catalogId,
        ];

        $this->repository->create([$media], $this->context);
        $filePath = $this->urlGenerator->getMediaUrl($media['id'], 'image/jpg', false);
        $this->filesystem->putStream($filePath, fopen(__DIR__ . '/../fixtures/shopware.jpg', 'r'));
    }

    private function createNewCatalog(): void
    {
        $catalogRepository = self::$container->get('catalog.repository');
        $this->catalogId = Uuid::uuid4()->getHex();
        $catalogRepository->create([['id' => $this->catalogId, 'name' => 'test catalog']], $this->context);
        $this->context = $this->context->createWithCatalogIds([$this->catalogId]);
    }
}