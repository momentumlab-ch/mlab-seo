"""Writes the translation files of the plugin.

Usage (from the repository root, Python 3 and PHP 8 needed, no other tools):
    python3 bin/i18n/build.py --missing   lists texts without translation
    python3 bin/i18n/build.py             adds the translations of bin/i18n/translations.py to the .po files and
                                          rebuilds .mo, .l10n.php, the JSON files for JavaScript and the .pot

New texts: add an entry to T (or P for plurals) in translations.py, German first, then French, then Italian.
Existing translations are never changed. The version in the file headers is read from mlab-seo.php.
"""
import json, re, sys, hashlib, struct, datetime, os, subprocess
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(os.path.dirname(HERE))
sys.path.insert(0, HERE)
import translations as trans
L = os.path.join(ROOT, 'languages') + os.sep
VERSION = re.search(r"'MLAB_SEO_VERSION', '([^']+)'", open(os.path.join(ROOT, 'mlab-seo.php'), encoding='utf8').read()).group(1)
NOW = datetime.datetime.now(datetime.timezone.utc)
src = json.loads(subprocess.run(['php', os.path.join(HERE, 'extract.php'), ROOT], capture_output=True, text=True, check=True).stdout)
files = {}
for e in src:
    files.setdefault((e['id'], e.get('ctx')), [])
    if e['file'] not in files[(e['id'], e.get('ctx'))]:
        files[(e['id'], e.get('ctx'))].append(e['file'])

def unq(s): return re.sub(r'\\(.)', lambda m: {'n': '\n', 't': '\t'}.get(m.group(1), m.group(1)), s)
def q(s): return s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n').replace('\t', '\\t')

def parse(path):
    ents, cur, key = [], None, None
    def new(): return {'c': [], 'ctx': None, 'id': None, 'plural': None, 'str': {}}
    cur = new()
    for line in open(path, encoding='utf8').read().split('\n'):
        if line.startswith('#'):
            if cur['id'] is not None:  # comment after an entry started: entry finished
                ents.append(cur); cur = new(); key = None
            cur['c'].append(line); continue
        m = re.match(r'(msgctxt|msgid_plural|msgid|msgstr(?:\[(\d)\])?) "(.*)"$', line)
        if m:
            k = m.group(1)
            if k == 'msgid' and cur['id'] is not None:
                ents.append(cur); cur = new()
            key = k; val = unq(m.group(3))
            if k == 'msgctxt': cur['ctx'] = val
            elif k == 'msgid': cur['id'] = val
            elif k == 'msgid_plural': cur['plural'] = val
            else: cur['str'][int(m.group(2) or 0)] = val
        elif line.startswith('"') and key:
            val = unq(line[1:-1])
            if key == 'msgctxt': cur['ctx'] += val
            elif key == 'msgid': cur['id'] += val
            elif key == 'msgid_plural': cur['plural'] += val
            else:
                i = int(re.search(r'\[(\d)\]', key).group(1)) if '[' in key else 0
                cur['str'][i] += val
    if cur['id'] is not None: ents.append(cur)
    return ents

def fmt(e, pot=False):
    out = list(e['c'])
    if e['ctx'] is not None: out.append('msgctxt "%s"' % q(e['ctx']))
    out.append('msgid "%s"' % q(e['id']))
    if e['plural'] is not None:
        out.append('msgid_plural "%s"' % q(e['plural']))
        for i in (0, 1): out.append('msgstr[%d] "%s"' % (i, '' if pot else q(e['str'].get(i, ''))))
    else:
        out.append('msgstr "%s"' % ('' if pot else q(e['str'].get(0, ''))))
    return '\n'.join(out)

def header_set(msg, key, val):
    return re.sub(r'(?m)^%s:.*$' % key, '%s: %s' % (key, val), msg)

def build_new(idx):
    new = []
    for i, (id_, tr) in enumerate(sorted(trans.T.items())):
        new.append({'c': ['#: ' + ' '.join(files.get((id_, None), []))], 'ctx': None, 'id': id_, 'plural': None, 'str': {0: tr[idx]}})
    for id_, (pl, de, fr, it) in sorted(trans.P.items()):
        t = (de, fr, it)[idx]
        new.append({'c': ['#: ' + ' '.join(files.get((id_, None), []))], 'ctx': None, 'id': id_, 'plural': pl, 'str': {0: t[0], 1: t[1]}})
    return new

def write_mo(path, msgs):
    keys = sorted(msgs, key=lambda k: k.encode('utf8'))
    ids = b''; strs = b''; offs = []
    for k in keys:
        kb, vb = k.encode('utf8'), msgs[k].encode('utf8')
        offs.append((len(ids), len(kb), len(strs), len(vb)))
        ids += kb + b'\0'; strs += vb + b'\0'
    n = len(keys); start = 28 + n * 16
    out = struct.pack('<7I', 0x950412de, 0, n, 28, 28 + n * 8, 0, 0)
    otab = b''.join(struct.pack('<2I', l, start + o) for o, l, _, _ in offs)
    ttab = b''.join(struct.pack('<2I', l, start + len(ids) + o) for _, _, o, l in offs)
    open(path, 'wb').write(out + otab + ttab + ids + strs)

def phpq(s):
    parts = s.split('\0')
    f = lambda x: "'" + x.replace('\\', '\\\\').replace("'", "\\'") + "'"
    return ' . "\\0" . '.join(f(p) for p in parts)

def write_php(path, hdr, msgs):
    h = lambda k: hdr.get(k, '')
    pl = re.search(r'Plural-Forms: (.*)', hdr['_raw']).group(1)
    items = ','.join("%s=>%s" % (phpq(k), phpq(v)) for k, v in msgs.items())
    meta = "'domain'=>'mlab-seo','plural-forms'=>%s,'language'=>%s,'project-id-version'=>%s,'pot-creation-date'=>%s,'po-revision-date'=>%s,'x-generator'=>%s," % tuple(phpq(x) for x in (pl.rstrip(';'), h('Language'), h('Project-Id-Version'), h('POT-Creation-Date'), h('PO-Revision-Date'), h('X-Generator')))
    open(path, 'w', encoding='utf8').write("<?php\nreturn [" + meta + "'messages'=>[" + items + "]];\n")

def hdr_dict(msg):
    d = {'_raw': msg}
    for line in msg.split('\n'):
        if ': ' in line: k, v = line.split(': ', 1); d[k] = v
    return d

def write_json(locale, hdr, trmap):
    jsfiles = sorted({f for fs in files.values() for f in fs if f.endswith('.js')})
    for f in jsfiles:
        h = hashlib.md5(f.encode()).hexdigest()
        ids = sorted({i for (i, c), fs in files.items() if f in fs})
        msgs = {'': {'domain': 'messages', 'lang': locale, 'plural-forms': re.search(r'Plural-Forms: (.*?);?$', hdr['_raw'], re.M).group(1) + ';'}}
        for i in ids:
            if trmap.get(i): msgs[i] = [trmap[i]]
        doc = {'translation-revision-date': hdr['PO-Revision-Date'], 'generator': 'WP-CLI/2.12.0', 'source': f, 'domain': 'messages', 'locale_data': {'messages': msgs}}
        s = json.dumps(doc, ensure_ascii=False, separators=(',', ':')).replace('/', '\\/')
        open('%smlab-seo-%s-%s.json' % (L, locale, h), 'w', encoding='utf8').write(s)

if '--missing' in sys.argv:
    have = {(e['id'], e['ctx']) for e in parse(L + 'mlab-seo-de_CH.po')}
    todo = sorted({e['id'] for e in src if (e['id'], e.get('ctx')) not in have} - set(trans.T) - set(trans.P))
    print('\n'.join(todo) if todo else 'Nothing missing.')
    sys.exit(0)
DRY = '--dry' in sys.argv
rev = NOW.strftime('%Y-%m-%d %H:%M+0000')
for locale, idx in (('de_CH', 0), ('de_CH_informal', 0), ('de_DE', 0), ('de_DE_formal', 0), ('fr_FR', 1), ('it_IT', 2)):
    path = L + 'mlab-seo-%s.po' % locale
    ents = parse(path)
    have = {(e['id'], e['ctx']) for e in ents}
    add = [e for e in build_new(idx) if (e['id'], e['ctx']) not in have]
    head = ents[0]; assert head['id'] == ''
    msg = head['str'][0]
    msg = header_set(msg, 'Project-Id-Version', 'MomentumLab SEO ' + VERSION)
    msg = header_set(msg, 'PO-Revision-Date', rev)
    msg = header_set(msg, 'POT-Creation-Date', NOW.strftime('%Y-%m-%dT%H:%M:%S+00:00'))
    head['str'][0] = msg
    allents = ents + add
    if DRY: print(locale, len(ents), '+', len(add)); continue
    text = '\n\n'.join(fmt(e) for e in allents) + '\n'
    open(path, 'w', encoding='utf8').write(text)
    msgs = {}
    mo = {}
    for e in allents:
        key = (e['ctx'] + '\x04' if e['ctx'] is not None else '') + e['id']
        if e['plural'] is not None:
            if e['str'].get(0):
                msgs[key] = '\0'.join(e['str'].get(i, '') for i in (0, 1))
                mo[key + '\0' + e['plural']] = msgs[key]
                continue
        elif e['str'].get(0) or e['id'] == '': msgs[key] = e['str'].get(0, '')
        if e['plural'] is None and key in msgs: mo[key] = msgs[key]
    hdr = hdr_dict(msg)
    write_mo(L + 'mlab-seo-%s.mo' % locale, mo)
    msgs2 = {k: v for k, v in msgs.items() if k != ''}
    write_php(L + 'mlab-seo-%s.l10n.php' % locale, hdr, msgs2)
    trmap = {e['id']: e['str'].get(0) for e in allents if e['ctx'] is None and e['plural'] is None and e['str'].get(0)}
    write_json(locale, hdr, trmap)
    print(locale, 'ok', len(msgs))
# pot
pot = L + 'mlab-seo.pot'
if not DRY:
    pents = parse(pot); have = {(e['id'], e['ctx']) for e in pents}
    add = [dict(e, str={}) for e in build_new(0) if (e['id'], e['ctx']) not in have]
    pents[0]['str'][0] = header_set(header_set(pents[0]['str'][0], 'Project-Id-Version', 'MomentumLab SEO ' + VERSION), 'POT-Creation-Date', NOW.strftime('%Y-%m-%dT%H:%M:%S+00:00'))
    open(pot, 'w', encoding='utf8').write('\n\n'.join(fmt(e, True) for e in pents + add) + '\n')
    print('pot ok')
