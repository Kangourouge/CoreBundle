<?php

namespace KRG\CoreBundle\Export;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use SimpleExcel\SimpleExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Templating\EngineInterface;

/**
 * Class XlsExport
 * @see https://docs.microsoft.com/en-us/previous-versions/office/developer/office-xp/aa140066(v=office.10)
 * @see https://phpspreadsheet.readthedocs.io/en/develop/topics/file-formats/#xml
 */
class XlsExport implements ExportInterface
{
    /** @var EngineInterface */
    protected $templating;

    /** @var string */
    protected $settings;

    /** @var string */
    protected $webDir;

    /**
     * XlsExport constructor.
     *
     * @param EngineInterface $templating
     * @param string $webDir
     */
    public function __construct(EngineInterface $templating, array $exportSettings, string $webDir)
    {
        $this->templating = $templating;
        $this->settings = $exportSettings['xls'];
        $this->webDir = $webDir;
    }

    public function render($filename, array $data, array $options = [])
    {
        $template = $options['template'] ?? '@KRGCore/export/layout.xml.twig';

        $data['settings'] = array_replace_recursive($this->settings, $options['settings'] ?? [], $data['settings'] ?? []);

        $xml = $this->templating->render($template, $data);

        $_filename = sprintf('%s.xml', $filename);

        file_put_contents($_filename, $xml);

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xml();
        $spreadsheet = $reader->load($_filename);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($filename);

        return new BinaryFileResponse($filename, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => sprintf('attachment; filename="%s"', basename($filename, true))
        ]);
    }
}