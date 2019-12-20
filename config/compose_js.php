<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

return $config = array(
    'internal_js' => array('stepper', 'csv_validator','tln', 'mmp' ),
    'external_js' => array(
        '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8.2.4/dist/sweetalert2.all.min.js" integrity="sha256-G83CHUL43nu8OZ2zyBVK4hXi1JydCwBZPabp7ufO7Cc=" crossorigin="anonymous"></script>',
        '<script src="https://unpkg.com/papaparse@4.6.3/papaparse.min.js"></script>',
        '<script src="https://cdn.datatables.net/v/dt/dt-1.10.20/b-1.6.1/b-colvis-1.6.1/fc-3.3.0/fh-3.1.6/r-2.2.3/sc-2.0.1/datatables.min.js"></script>',
        '<script src="http://parsleyjs.org/dist/parsley.min.js"></script>',
        // '<script src="https://cdn.lr-ingest.io/LogRocket.min.js" crossorigin="anonymous"></script>'
    ),
);
//EOF