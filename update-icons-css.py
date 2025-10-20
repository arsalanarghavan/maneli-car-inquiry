#!/usr/bin/env python3

icons = [
    ('user', 'f007'), ('users', 'f0c0'), ('car', 'f1b9'),
    ('dollar', 'f155'), ('dollar-sign', 'f155'), ('chart-bar', 'f080'),
    ('chart-line', 'f201'), ('chart-line-down', 'f03d'), ('cog', 'f013'),
    ('calendar', 'f133'), ('calendar-alt', 'f073'), ('calendar-check', 'f274'),
    ('search', 'f002'), ('envelope', 'f0e0'), ('lock', 'f023'),
    ('heart', 'f004'), ('star', 'f005'), ('plus', 'f067'),
    ('plus-circle', 'f055'), ('minus', 'f068'), ('times', 'f00d'),
    ('times-circle', 'f057'), ('check', 'f00c'), ('check-circle', 'f058'),
    ('exclamation-triangle', 'f071'), ('exclamation-circle', 'f06a'),
    ('info-circle', 'f05a'), ('eye', 'f06e'), ('eye-slash', 'f070'),
    ('edit', 'f044'), ('trash', 'f1f8'), ('save', 'f0c7'),
    ('sync', 'f021'), ('arrow-up', 'f062'), ('arrow-down', 'f063'),
    ('arrow-left', 'f060'), ('arrow-right', 'f061'), ('angle-down', 'f107'),
    ('filter', 'f0b0'), ('share', 'f064'), ('list', 'f03a'),
    ('list-alt', 'f022'), ('shopping-cart', 'f07a'), ('credit-card', 'f09d'),
    ('money-bill', 'f0d6'), ('file-alt', 'f15c'), ('inbox', 'f01c'),
    ('bell-slash', 'f1f6'), ('comment', 'f075'), ('paper-plane', 'f1d8'),
    ('sign-in-alt', 'f2f6'), ('sign-out-alt', 'f2f5'), ('mobile', 'f10b'),
    ('ticket-alt', 'f3ff'), ('calculator', 'f1ec'), ('user-plus', 'f234'),
    ('user-cog', 'f4fe'), ('user-alt', 'f406'), ('users-cog', 'f509'),
    ('toggle-on', 'f205'), ('toggle-off', 'f204'), ('id-card', 'f2c2'),
    ('pencil', 'f040'), ('clock', 'f017'), ('spinner', 'f110'),
    ('link', 'f0c1'),
]

for name, code in icons:
    print(f'.la.la-{name}:before,')
    print(f'.las.la-{name}:before,')
    print(f'.la-{name}:before {{ content: "\\{code}"; }}')
    print()
