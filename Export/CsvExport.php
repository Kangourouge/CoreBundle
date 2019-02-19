<?php

namespace KRG\CoreBundle\Export;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Translation\TranslatorInterface;

class CsvExport implements ExportInterface
{

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $settings;

    /**
     * CsvExport constructor.
     *
     * @param TranslatorInterface $translator
     * @param array $exportSettings
     */
    public function __construct(TranslatorInterface $translator, array $exportSettings)
    {
        $this->translator = $translator;
        $this->settings = $exportSettings['csv'];
    }

    public function render($filename, array $data, array $options = [])
    {
        $settings = array_replace_recursive($this->settings, $options['settings'] ?? []);

        $file = new \SplFileObject($filename, 'w');
        $file->setCsvControl($settings['delimiter'], $settings['enclosure'], $settings['escape_char']);


        foreach ($data['sheets'] as $sheet) {
            foreach ($sheet['tables'] as $table) {
                foreach ($table['thead'] as $row) {
                    if (is_array($row)) {
                        $row = array_map(function($label) { return $this->translator->trans($label); }, $row);
                        $file->fputcsv($row);
                    }
                }

                foreach ($table['tbody'] as $row) {
                    if (is_array($row)) {
                        $file->fputcsv($row);
                    }
                }

                foreach ($table['tfoot'] as $row) {
                    if (is_array($row)) {
                        $file->fputcsv($row);
                    }
                }
                $file->fputcsv([]);
                $file->fputcsv([]);
                $file->fputcsv([]);
            }
        }

        return new BinaryFileResponse($file, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', basename($filename, true))
        ]);
    }
}
