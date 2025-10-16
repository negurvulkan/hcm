<?php
namespace App\Services;

class PdfService
{
    public function downloadSimplePdf(string $title, array $lines): void
    {
        $content = $this->buildPdf($title, $lines);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $this->slug($title) . '.pdf"');
        header('Content-Length: ' . strlen($content));
        echo $content;
    }

    private function buildPdf(string $title, array $lines): string
    {
        $objects = [];
        $offsets = [];

        $pdfHeader = "%PDF-1.4\n";

        $objects[] = "1 0 obj<< /Type /Font /Subtype /Type1 /Name /F1 /BaseFont /Helvetica >>endobj";

        $contentStream = $this->buildContentStream($title, $lines);
        $objects[] = "2 0 obj<< /Length " . strlen($contentStream) . " >>stream\n" . $contentStream . "\nendstream endobj";

        $objects[] = "3 0 obj<< /Type /Page /Parent 4 0 R /MediaBox [0 0 595 842] /Contents 2 0 R /Resources << /Font << /F1 1 0 R >> >> >>endobj";

        $objects[] = "4 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj";

        $objects[] = "5 0 obj<< /Type /Catalog /Pages 4 0 R >>endobj";

        $body = '';
        $currentOffset = strlen($pdfHeader);
        foreach ($objects as $object) {
            $offsets[] = $currentOffset;
            $body .= $object . "\n";
            $currentOffset += strlen($object) + 1;
        }

        $xrefOffset = $currentOffset;
        $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $xref .= sprintf('%010d 00000 n ', $offset) . "\n";
        }

        $trailer = "trailer<< /Size " . (count($objects) + 1) . " /Root 5 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdfHeader . $body . $xref . $trailer;
    }

    private function buildContentStream(string $title, array $lines): string
    {
        $content = "BT /F1 20 Tf 50 780 Td (" . $this->escapeText($title) . ") Tj ET\n";
        $y = 750;
        foreach ($lines as $line) {
            $content .= "BT /F1 12 Tf 50 {$y} Td (" . $this->escapeText($line) . ") Tj ET\n";
            $y -= 20;
        }
        return $content;
    }

    private function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function slug(string $title): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $title));
        return trim($slug, '-') ?: 'dokument';
    }
}
