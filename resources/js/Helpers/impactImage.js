const photos = [
    '/imgs/web/md/IMG20261028134003.jpg',
    '/imgs/web/md/IMG20261028134004.jpg',
    '/imgs/web/md/IMG20261028134006.jpg',
    '/imgs/web/md/IMG20261028134007.jpg',
    '/imgs/web/md/IMG20261028134008.jpg',
    '/imgs/web/md/IMG20261028134009.jpg',
    '/imgs/web/md/IMG20261028134049.jpg',
    '/imgs/web/md/IMG20261028134052.jpg',
];

const map = {
    supported: photos[0],
    in_school: photos[1],
    active_secondary: photos[1],
    active_university: photos[2],
    graduates: photos[3],
    msce: photos[4],
    families: photos[5],
    repairs: photos[6],
    businesses: photos[7],
    alumni_active: photos[2],
};

export function imageForMetric(key) {
    return map[key] ?? photos[0];
}
