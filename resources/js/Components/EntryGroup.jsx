export default function EntryGroup({ group, index }) {
    return (
        <div className={`bg-${index % 2 === 0 ? 'gray-50' : 'white'} p-4 mb-2 rounded`}>
            {/* Group Header */}
            <div className="border-b border-gray-200 pb-2 mb-3">
                <div className="flex justify-between items-center">
                    <div>
                        <span className="font-medium">{group.main_entry.recipient}</span>
                        <span className="mx-2">•</span>
                        <span>{group.main_entry.description}</span>
                    </div>
                    <span className="font-medium">
                        Total: {new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(group.total_amount)}
                    </span>
                </div>
            </div>

            {/* Entries */}
            <div className="space-y-2">
                <EntryRow entry={group.main_entry} />
                {group.similar_entries.map(entry => (
                    <EntryRow key={entry.id} entry={entry} />
                ))}
            </div>
        </div>
    );
}

function EntryRow({ entry }) {
    return (
        <div className="flex justify-between items-center text-sm pl-4">
            <div className="flex-1 grid grid-cols-3 gap-4">
                <span className="text-gray-600">
                    {new Date(entry.transaction_date).toLocaleDateString()}
                </span>
                <span className="text-gray-800">{entry.recipient}</span>
                <span className="text-gray-600">{entry.description}</span>
            </div>
            <span className="text-gray-700">
                {new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(entry.amount)}
            </span>
        </div>
    );
}
