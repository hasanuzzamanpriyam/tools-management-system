export default function Placeholder({ title, description }) {
    return (
        <div>
            <p className="text-sm text-slate-500">{description}</p>
            <div className="mt-6 rounded-xl border-2 border-dashed border-slate-200 bg-white p-16 text-center">
                <p className="text-sm font-medium text-slate-400">
                    The {title} module is coming soon.
                </p>
            </div>
        </div>
    );
}