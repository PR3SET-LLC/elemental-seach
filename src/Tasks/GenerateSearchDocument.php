<?php
/**
 * Created by Nivanka Fonseka (nivanka@silverstripers.com).
 * User: nivankafonseka
 * Date: 9/7/18
 * Time: 12:32 PM
 * To change this template use File | Settings | File Templates.
 */

namespace SilverStripers\ElementalSearch\Tasks;


use Exception;
use Override;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripers\ElementalSearch\Extensions\SearchDocumentGenerator;
use SilverStripers\ElementalSearch\Extensions\SiteTreeDocumentGenerator;
use Symfony\Component\Console\Input\InputInterface;

class GenerateSearchDocument extends BuildTask
{
    protected string $title = 'Re-generate all search documents';

    protected static string $description = 'Generate search documents for items.';

    private static string $segment = 'make-search-docs';

    /**
     * @param InputInterface $input
     * @param PolyOutput $output
     * @return int Return code (0 for success, 1 for error)
     */
    #[Override]
    public function run(InputInterface $input, PolyOutput $output): int
    {
        return $this->execute($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param PolyOutput $output
     * @return int Return code (0 for success, 1 for error)
     */
    public function execute(InputInterface $input, PolyOutput $output): int
    {
        $eol = Director::is_cli() ? PHP_EOL . PHP_EOL : '<br>';
        set_time_limit(50000);
        $classes = $this->getAllSearchDocClasses();
        
        foreach ($classes as $class) {
            foreach ($list = DataList::create($class) as $record) {
                $output = sprintf(
                    'Making record for %s type %s, link %s',
                    $record->getTitle(),
                    $record->ClassName,
                    ClassInfo::hasMethod($record, 'getGenerateSearchLink') ? $record->getGenerateSearchLink() : $record->Title
                );

                $output .= $eol;
                echo $output;

                try {
                    SearchDocumentGenerator::make_document_for($record);
                } catch (Exception $e) {
                    $output->writeln('Error processing record ' . $record->ID . ': ' . $e->getMessage());
                    return 1; // Return error code
                }
            }
        }
        
        echo 'Completed' . $eol;
        return 0; // Success
    }

    public function getAllSearchDocClasses(): array
    {
        $list = [];
        foreach (ClassInfo::subclassesFor(DataObject::class) as $class) {
            $configs = Config::inst()->get($class, 'extensions', Config::UNINHERITED);
            if($configs) {
                $valid = in_array(SearchDocumentGenerator::class, $configs)
                    || in_array(SiteTreeDocumentGenerator::class, $configs);

                if ($valid) {
                    $list[] = $class;
                }
            }
        }
        return $list;
    }

}
