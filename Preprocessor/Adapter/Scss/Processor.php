<?php
/**
 * compile scss files
 */
namespace Rich\Scss\Preprocessor\Adapter\Scss;

use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\View\Asset\ContentProcessorInterface;
use Leafo\ScssPhp\Compiler;
use Magento\Framework\View\Design\Theme\ThemePackageList;

/**
 * Class Processor
 */
class Processor implements ContentProcessorInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Source
     */
    private $assetSource;

    /**
     * @var ThemePackageList
     */
    private $themePackageList;

    /**
     * @var array
     */
    private $themePaths;

    /**
     * Constructor
     *
     * @param Source $assetSource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Source $assetSource,
        LoggerInterface $logger,
        ThemePackageList $themePackageList,
        $themePaths = []
    )
    {
        $this->assetSource = $assetSource;
        $this->logger = $logger;
        $this->themePackageList = $themePackageList;
        $this->themePaths = $themePaths;
    }

    /**
     * Process file content
     *
     * @param File $asset
     * @return string
     */
    public function processContent(File $asset)
    {
        $path = $asset->getPath();
        $context = $asset->getContext();

        //I believe the context class is set in
        // Magento\Developer\Model\View\Asset\PreProcessor\FrontendCompilation::processContent
        /** @var File\FallbackContext $context */
        //get 'theme key' to get theme file path to resolve @import directives
        $themeKey = $context->getAreaCode() . '/' . $context->getThemePath();

        try {
            $themePath = $this->themePackageList->getTheme($themeKey)->getPath();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logger->critical($errorMessage);

            return $errorMessage;
        }

        try {
            $compiler = new Compiler();

            //add import path so @import directives will work (http://leafo.net/scssphp/docs/#import_paths)
            $compiler->addImportPath($themePath . '/web/css');

            $content = $this->assetSource->getContent($asset);

            if (trim($content) === '') {
                return '';
            }

            $compile = $compiler->compile($content);

            return $compile;
        } catch (\Exception $e) {
            $errorMessage = PHP_EOL . self::ERROR_MESSAGE_PREFIX . PHP_EOL . $path . PHP_EOL . $e->getMessage();
            $this->logger->critical($errorMessage);

            return $errorMessage;
        }
    }
}