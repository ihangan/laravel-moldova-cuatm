<div>
    @foreach ($levels as $level => $options)
        <select wire:change="choose({{ $level }}, $event.target.value)">
            <option value="">&mdash;</option>

            @foreach ($options as $location)
                <option value="{{ $location->id }}" @selected(($path[$level] ?? null) === $location->id)>
                    {{ $location->name }} ({{ $location->type->label() }})
                </option>
            @endforeach
        </select>
    @endforeach
</div>
