function Table({ columns, data, emptyText = 'Chưa có dữ liệu' }) {
  return (
    <div className="overflow-hidden rounded-lg border border-brand-border bg-brand-white">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-brand-border text-left text-sm">
          <thead className="bg-brand-bg text-xs uppercase tracking-wide text-brand-muted">
            <tr>
              {columns.map((column) => (
                <th className="px-4 py-3 font-semibold" key={column.key}>
                  {column.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-brand-border text-brand-text">
            {data.length ? (
              data.map((row) => (
                <tr className="hover:bg-brand-bg" key={row.row_key || row.id}>
                  {columns.map((column) => (
                    <td className="px-4 py-3" key={column.key}>
                      {column.render ? column.render(row) : row[column.key]}
                    </td>
                  ))}
                </tr>
              ))
            ) : (
              <tr>
                <td className="px-4 py-8 text-center text-brand-muted" colSpan={columns.length}>
                  {emptyText}
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}

export default Table
