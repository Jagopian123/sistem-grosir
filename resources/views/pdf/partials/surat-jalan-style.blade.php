<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #1a1a1a; }

    .page { padding: 24px 32px; }
    /* Pemisah halaman antar surat jalan saat cetak massal. */
    .page-break { page-break-after: always; }

    /* Header */
    .header { border-bottom: 2px solid #1a1a1a; padding-bottom: 10px; margin-bottom: 14px; }
    .header-inner { width: 100%; }
    .toko-nama { font-size: 16pt; font-weight: bold; }
    .toko-sub { font-size: 9pt; color: #555; margin-top: 2px; }
    .doc-title { text-align: right; }
    .doc-title h2 { font-size: 14pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .doc-title p { font-size: 9pt; color: #555; margin-top: 2px; }

    /* Meta */
    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .meta-table td { padding: 3px 0; font-size: 10pt; vertical-align: top; }
    .meta-label { width: 130px; color: #555; }
    .meta-sep { width: 10px; }

    .box { border: 1px solid #ccc; border-radius: 4px; padding: 10px 14px; margin-bottom: 14px; }
    .box-title { font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #777; margin-bottom: 6px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
    .box-content { font-size: 10pt; }
    .box-content .nama { font-weight: bold; font-size: 11pt; }
    .box-content .sub { color: #444; margin-top: 2px; }

    .two-col { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .two-col td { vertical-align: top; }
    .col-left { width: 49%; padding-right: 8px; }
    .col-right { width: 49%; padding-left: 8px; }

    /* Items table */
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .items-table th { background: #f0f0f0; font-size: 9pt; text-align: left; padding: 6px 8px; border: 1px solid #ccc; }
    .items-table td { font-size: 10pt; padding: 6px 8px; border: 1px solid #ccc; vertical-align: top; }
    .items-table tr:nth-child(even) td { background: #fafafa; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }

    /* Total row */
    .total-row td { font-weight: bold; background: #f0f0f0 !important; border-top: 2px solid #aaa; }

    /* Footer */
    .sign-table { width: 100%; border-collapse: collapse; margin-top: 28px; }
    .sign-table td { text-align: center; vertical-align: top; width: 33%; padding: 0 8px; }
    .sign-box { border: 1px solid #ccc; padding: 60px 8px 8px; font-size: 9pt; border-radius: 3px; }
    .sign-label { font-size: 9pt; font-weight: bold; margin-bottom: 6px; }

    .note { font-size: 9pt; color: #555; border-top: 1px dashed #ccc; padding-top: 8px; margin-top: 8px; }
</style>
