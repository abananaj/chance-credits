import { useState, useEffect } from '@wordpress/element';
import { Button, SelectControl, TextControl, ComboboxControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const ROLE_GROUP_OPTIONS = [
  { label: 'Playwright', value: 'playwright' },
  { label: 'Actor', value: 'actor' },
  { label: 'Director', value: 'director' },
  { label: 'Choreographer', value: 'choreographer' },
  { label: 'Designer', value: 'designer' },
  { label: 'Producer', value: 'producer' },
  { label: 'Other', value: 'other' },
];

export default function CreditRow({ row, isFirst, isLast, onChange, onDelete, onMoveUp, onMoveDown }) {
  const [artistOptions, setArtistOptions] = useState(
    row.artist_id
      ? [{ value: row.artist_id, label: row.artist_title || String(row.artist_id) }]
      : []
  );

  useEffect(() => {
    // Reset artist options when role_group changes between producer and non-producer
  }, [row.role_group]);

  function searchArtists(filterValue) {
    if (filterValue.length < 2) return;
    const subtype = row.role_group === 'producer' ? 'support' : 'artist';
    apiFetch({
      path: `/wp/v2/search?search=${encodeURIComponent(filterValue)}&type=post&subtype=${subtype}&per_page=20&_fields=id,title`,
    })
      .then((results) => {
        setArtistOptions(results.map((r) => ({ value: r.id, label: r.title })));
      })
      .catch(() => {});
  }

  function handleRoleGroupChange(value) {
    const wasProducer = row.role_group === 'producer';
    const isNowProducer = value === 'producer';
    if (wasProducer !== isNowProducer) {
      onChange({ role_group: value, artist_id: 0, artist_title: '' });
      setArtistOptions([]);
    } else {
      onChange({ role_group: value });
    }
  }

  return (
    <div className="credit-row">
      <div className="credit-row-fields">
        <ComboboxControl
          label="Artist"
          value={row.artist_id || null}
          onChange={(value) => {
            const option = artistOptions.find((o) => o.value === value);
            onChange({ artist_id: value, artist_title: option?.label || '' });
          }}
          options={artistOptions}
          onFilterValueChange={searchArtists}
          allowReset={false}
        />
        <SelectControl
          label="Role Group"
          value={row.role_group}
          options={ROLE_GROUP_OPTIONS}
          onChange={handleRoleGroupChange}
        />
        <TextControl
          label="Role"
          value={row.role}
          onChange={(value) => onChange({ role: value })}
          placeholder="e.g. Hamlet"
        />
      </div>
      <div className="credit-row-actions">
        <Button icon="arrow-up-alt2" label="Move up" onClick={onMoveUp} disabled={isFirst} size="small" />
        <Button icon="arrow-down-alt2" label="Move down" onClick={onMoveDown} disabled={isLast} size="small" />
        <Button icon="trash" label="Remove credit" onClick={onDelete} isDestructive size="small" />
      </div>
    </div>
  );
}
