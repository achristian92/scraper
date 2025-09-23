<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\Worker;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class CredentialController extends Controller
{
    public function index()
    {
        $workers = Worker::orderBy('full_name')->get();

        $extracted = [
            'TipoCredencial' => '',
            'Nombres' => '',
            'ApellidoPaterno' => '',
            'ApellidoMaterno' => '',
            'EmpresaTrabaja' => '',
            'Cargo' => '',
            'Telefono' => '',
            'Email' => '',
        ];


        return view('credentials.index', [
            'workers' => $workers,
            'selectedWorkerId' => null,
            'sourceUrl' => null,
            'extracted' => $extracted,
        ]);
   }

    public function contacts()
   {
       $contacts = Credential::with('worker')->orderByDesc('id')->paginate(20);

       return view('credentials.contacts', compact('contacts'));
   }
    public function extract(Request $request)
    {
        $data = $request->validate([
            'worker_id' => ['required','exists:workers,id'],
            'source_url' => ['required','url'],
        ]);

        // 1) intentar leer parámetros de query (?NC=...)
        $parsed = [];
        $parts = parse_url($data['source_url']);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $parsed);
        }

        // 2) scrapear inputs del HTML (value por cada <input>) usando DomCrawler + HttpClient (compatible con Symfony 7 / Laravel 11)
        try {
            $client = HttpClient::create();
            $response = $client->request('GET', $data['source_url'], [
                'headers' => [
                    'User-Agent' => 'cred-scraper/1.0 (+Laravel HttpClient/Symfony)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
                'timeout' => 15,
            ]);

            $html = $response->getContent(); // lanza excepción si HTTP status no es 2xx
            $crawler = new Crawler($html, $data['source_url']);
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['source_url' => 'No se pudo leer la página: ' . $e->getMessage()])
                ->withInput();
        }

        // trae todos los inputs con name (evita duplicados vacíos)
        $inputs = $crawler->filter('input[name]')->each(function ($node) {
            $name = $node->attr('name');
            $value = $node->attr('value') ?? '';
            return [$name, $value];
        });

        foreach ($inputs as [$name, $value]) {
            // no pises valores de query si ya existían
            if (!array_key_exists($name, $parsed)) {
                $parsed[$name] = $value;
            }
        }

        // también puedes intentar select/textarea si lo necesitas
        $selects = $crawler->filter('select[name]')->each(function ($node) {
            $name = $node->attr('name');
            // opción seleccionada
            $selected = $node->filter('option[selected]')->count()
                ? $node->filter('option[selected]')->attr('value')
                : '';
            return [$name, $selected];
        });
        foreach ($selects as [$name, $value]) {
            if (!array_key_exists($name, $parsed)) {
                $parsed[$name] = $value;
            }
        }

        $textareas = $crawler->filter('textarea[name]')->each(function ($node) {
            // text(string $default = null, bool $normalizeWhitespace = true)
            return [$node->attr('name'), trim($node->text(''))];
        });
        foreach ($textareas as [$name, $value]) {
            if (!array_key_exists($name, $parsed)) {
                $parsed[$name] = $value;
            }
        }

        // pasa todo a la vista para mostrar formulario editable
        $workers = Worker::orderBy('full_name')->get();
        return view('credentials.index', [
            'workers' => $workers,
            'selectedWorkerId' => $data['worker_id'],
            'sourceUrl' => $data['source_url'],
            'extracted' => $parsed, // array name=>value
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'worker_id' => ['required','exists:workers,id'],
            'source_url' => ['nullable','url'],
            'payload' => ['required','array'],
        ]);

        $customer = $request->payload;

        $cred = Credential::create([
            'worker_id' => $request->worker_id,
            'source_url' => $request->source_url ?? null,
            'tipo'   => $customer['TipoCredencial'],
            'nombre'   => $customer['Nombres'],
            'ap_pate'   => $customer['ApellidoPaterno'],
            'ap_mat'   => $customer['ApellidoMaterno'],
            'empresa'   => $customer['EmpresaTrabaja'],
            'cargo'   => $customer['Cargo'],
            'telefono'   => $customer['Telefono'],
            'email'   => $customer['Email'],
            'payload'   => $request->payload,
        ]);

        return redirect()
            ->route('credentials.index')
            ->with('status', 'Datos guardados (ID: '.$cred->id.')');
    }

}
