<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormTemplate;
use App\Services\TemplateParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TemplateController extends Controller
{
    public function index()
    {
        return view('admin.templates.index', [
            'templates' => FormTemplate::withCount('evaluations')->with('uploader')->orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.templates.create');
    }

    public function store(Request $request, TemplateParser $parser)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'file' => ['required', 'file', 'max:'.config('evaluation.template_max_kb'), 'extensions:html,htm', 'mimetypes:text/html,text/plain,application/xhtml+xml'],
        ]);

        $file = $request->file('file');
        $contents = file_get_contents($file->getRealPath());

        if (!mb_check_encoding($contents, 'UTF-8')) {
            return back()->withErrors(['file' => 'Please save the file as UTF-8.']);
        }

        try {
            $parsed = $parser->process($contents);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $name = trim((string) $request->input('name')) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $template = DB::transaction(function () use ($request, $parsed, $name) {
            $version = (int) FormTemplate::where('name', $name)->max('version') + 1;
            $activate = $request->boolean('activate');
            if ($activate) {
                FormTemplate::where('is_active', true)->update(['is_active' => false]);
            }

            return FormTemplate::create([
                'name'        => $name,
                'version'     => $version,
                'html'        => $parsed['html'],
                'schema'      => $parsed['schema'],
                'is_active'   => $activate,
                'uploaded_by' => $request->user()->id,
            ]);
        });

        AuditLog::record('template.uploaded', "{$template->name} v{$template->version}");

        return redirect()->route('admin.templates.show', $template)
            ->with('status', 'Template uploaded and checked. Review the detected questions below.');
    }

    public function show(FormTemplate $template)
    {
        return view('admin.templates.show', compact('template'));
    }

    public function activate(FormTemplate $template)
    {
        DB::transaction(function () use ($template) {
            FormTemplate::where('is_active', true)->update(['is_active' => false]);
            $template->update(['is_active' => true]);
        });
        AuditLog::record('template.activated', "{$template->name} v{$template->version}");

        return back()->with('status', 'This template is now live on every room QR code.');
    }

    public function destroy(FormTemplate $template)
    {
        if ($template->evaluations()->exists()) {
            return back()->withErrors(['template' => 'This template already has responses, so it cannot be deleted. Activate a newer one instead.']);
        }
        if ($template->is_active) {
            return back()->withErrors(['template' => 'Activate another template before deleting the live one.']);
        }

        $template->delete();
        AuditLog::record('template.deleted', "{$template->name} v{$template->version}");

        return redirect()->route('admin.templates.index')->with('status', 'Template deleted.');
    }

    /** Download the bundled sample so admins can see the expected markup. */
    public function sample()
    {
        return response()->download(database_path('seeders/default_template.html'), 'sample-form-template.html');
    }
}
