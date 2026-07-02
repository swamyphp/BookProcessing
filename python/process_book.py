#!/usr/bin/env python3
import sys, json, os
from chapter_reader import read_chapters
from metadata import extract_metadata

def main():
    if len(sys.argv)<3:
        print(json.dumps({'status':'error','error':'args'})); return
    folder=sys.argv[1]; short=sys.argv[2]
    # find manuscript
    m=None
    for root,_,files in os.walk(folder):
        for f in files:
            if f.lower()=='manuscript.docx': m=os.path.join(root,f); break
        if m: break
    if not m:
        print(json.dumps({'status':'error','error':'manuscript missing'})); return
    meta = extract_metadata(m)
    chapters = read_chapters(m)
    chapter_list = []
    for idx,title in enumerate(chapters, start=1):
        chapter_list.append({'chapter': idx, 'title': title})
    out = {
        'status':'success',
        'book': short,
        'title': meta.get('title') or short,
        'chapters': len(chapter_list),
        'chapterList': chapter_list
    }
    print(json.dumps(out))

if __name__=='__main__': main()
