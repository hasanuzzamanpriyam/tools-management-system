import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../api/client';

const inputClasses =
    'mt-1 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';

function InfoRow({ label, value }) {
    return (
        <div>
            <dt className="text-xs font-semibold tracking-wide text-slate-500 uppercase">{label}</dt>
            <dd className="mt-1 text-sm text-slate-900">{value}</dd>
        </div>
    );
}

export default function ToolDetail() {
    const { id } = useParams();
    const [tool, setTool] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [version, setVersion] = useState('');
    const [changelog, setChangelog] = useState('');
    const [file, setFile] = useState(null);
    const [uploadError, setUploadError] = useState('');
    const [uploading, setUploading] = useState(false);

    const loadTool = useCallback(async () => {
        setLoading(true);

        try {
            const { data } = await api.get(`/admin/tools/${id}`);
            setTool(data.data ?? data);
            setError('');
        } catch {
            setError('Failed to load tool.');
        } finally {
            setLoading(false);
        }
    }, [id]);

    useEffect(() => {
        loadTool();
    }, [loadTool]);

    const handleUpload = async (event) => {
        event.preventDefault();
        setUploadError('');
        setUploading(true);

        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('version', version);
            formData.append('changelog', changelog);

            await api.post(`/admin/tools/${id}/files`, formData);
            setFile(null);
            setVersion('');
            setChangelog('');
            await loadTool();
        } catch (error) {
            const data = error.response?.data;
            const nested = data?.errors && Object.values(data.errors)[0];

            setUploadError((nested?.[0]) || data?.message || 'Upload failed. Please try again.');
        } finally {
            setUploading(false);
        }
    };

    const handleDeleteFile = async (fileId) => {
        if (!window.confirm('Remove this file?')) {
            return;
        }

        try {
            await api.delete(`/admin/tools/${id}/files/${fileId}`);
            await loadTool();
        } catch {
            setError('Failed to remove file.');
        }
    };

    if (loading) {
        return <div className="rounded-xl border border-slate-200 bg-white p-16 text-center text-sm text-slate-400">Loading tool…</div>;
    }

    if (error || !tool) {
        return (
            <div>
                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
                <Link to="/tools" className="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    ← Back to tools
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <Link to="/tools" className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    ← Back to tools
                </Link>
                <div className="mt-2 flex items-center gap-3">
                    <h1 className="text-2xl font-semibold text-slate-900">{tool.name}</h1>
                    <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">/{tool.slug}</span>
                    <span
                        className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${
                            tool.is_active ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-slate-100 text-slate-500 ring-slate-500/20'
                        }`}
                    >
                        <span className={`h-1.5 w-1.5 rounded-full ${tool.is_active ? 'bg-green-500' : 'bg-slate-400'}`}></span>
                        {tool.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="text-sm font-semibold text-slate-900">Details</h2>
                <dl className="mt-4 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                    <InfoRow label="Type" value={tool.type} />
                    <InfoRow label="Pricing model" value={tool.pricing_model} />
                    <InfoRow label="Price" value={`$${Number(tool.price).toFixed(2)}`} />
                    <InfoRow label="Device limit" value={tool.device_limit} />
                    <InfoRow label="Referral credits" value={tool.referral_credits} />
                    <InfoRow label="Files" value={tool.files?.length ?? 0} />
                </dl>
                <p className="mt-6 text-sm leading-relaxed text-slate-600">{tool.description}</p>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                    <h2 className="text-sm font-semibold text-slate-900">Upload a file</h2>
                    <p className="mt-1 text-xs text-slate-500">ZIP, EXE or MSI, up to 100 MB.</p>

                    {uploadError && (
                        <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {uploadError}
                        </div>
                    )}

                    <form className="mt-4 space-y-4" onSubmit={handleUpload}>
                        <div>
                            <label htmlFor="file" className="block text-sm font-medium text-slate-700">
                                File
                            </label>
                            <input
                                id="file"
                                type="file"
                                required
                                accept=".zip,.exe,.msi"
                                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                                className="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                            />
                        </div>
                        <div>
                            <label htmlFor="version" className="block text-sm font-medium text-slate-700">
                                Version
                            </label>
                            <input
                                id="version"
                                type="text"
                                required
                                value={version}
                                onChange={(e) => setVersion(e.target.value)}
                                className={inputClasses}
                                placeholder="1.0.0"
                            />
                        </div>
                        <div>
                            <label htmlFor="changelog" className="block text-sm font-medium text-slate-700">
                                Changelog <span className="font-normal text-slate-400">(optional)</span>
                            </label>
                            <textarea
                                id="changelog"
                                rows={3}
                                value={changelog}
                                onChange={(e) => setChangelog(e.target.value)}
                                className={inputClasses}
                                placeholder="What changed in this release?"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={uploading || !file}
                            className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {uploading ? 'Uploading…' : 'Upload file'}
                        </button>
                    </form>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                    <div className="border-b border-slate-100 px-6 py-4">
                        <h2 className="text-sm font-semibold text-slate-900">Distribution files</h2>
                    </div>

                    {tool.files?.length ? (
                        <ul className="divide-y divide-slate-100">
                            {tool.files.map((meta) => (
                                <li key={meta.id} className="flex items-center justify-between gap-4 px-6 py-4">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-slate-900">
                                            {meta.file_path.split('/').pop()}
                                        </p>
                                        <div className="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                            <span className="rounded bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600 uppercase">
                                                {meta.file_type}
                                            </span>
                                            <span>v{meta.version}</span>
                                            {meta.changelog && <span className="truncate text-slate-400">— {meta.changelog}</span>}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => handleDeleteFile(meta.id)}
                                        className="shrink-0 text-sm font-medium text-red-600 hover:text-red-700"
                                    >
                                        Remove
                                    </button>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="p-10 text-center text-sm text-slate-400">No files uploaded yet.</div>
                    )}
                </div>
            </div>
        </div>
    );
}