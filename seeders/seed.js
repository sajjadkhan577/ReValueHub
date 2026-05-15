const User = require('../models/User');
const Item = require('../models/Item');

async function seed() {
  console.log('🌱 Checking seed data...');

  // Create dummy users if they do not already exist.
  const [adminUser] = await User.findOrCreate({
    where: { email: 'admin@revalue.com' },
    defaults: {
    name: 'Admin User',
    password: 'admin123',
    phone: '+1-555-0100',
    role: 'admin'
    }
  });

  const [regularUser] = await User.findOrCreate({
    where: { email: 'sarah@revalue.com' },
    defaults: {
    name: 'Sarah Jenkins',
    password: 'user123',
    phone: '+1-555-0200',
    role: 'user'
    }
  });

  const [demoUser] = await User.findOrCreate({
    where: { email: 'david@revalue.com' },
    defaults: {
    name: 'David Chen',
    password: 'user123',
    phone: '+1-555-0300',
    role: 'user'
    }
  });

  const itemCount = await Item.count();
  if (itemCount > 0) {
    console.log('📦 Database already has demo items, skipping item seed...');
    return;
  }

  // Dummy items with real images from the existing HTML
  const items = [
    {
      title: 'Old Study Table',
      description: 'A sturdy wooden study table in good condition. Perfect for students or a home office setup. Minor scratches on the surface but fully functional with two drawers.',
      category: 'furniture',
      location: 'Brooklyn, NY',
      condition: 'good',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBwUi6j0Q6wCJFwoFXayrwd0iC-zZCywOQN7nPC5QHwbVeHh3BPsRTqUWu-BHaoN-uTPIlUrM2be8uSdmSwQe0GNhoazt5cKrbyiP1El911rgdWrbt-xKRpCNalz_8AS2QoRdwrXZblK76pW1a4_aGfB6L5ApGObS1fIuj0LGjmqGmEsATmMHs4FNwfXQEtrevaBrEph4j4ThT864EdwrUnjKyZYXJoqg-IucjN8AnScRxJUZyAmAmnTq5at_aMc8n2qfvxtSguKfc',
      user_id: regularUser.id
    },
    {
      title: 'Used Laptop Bag',
      description: 'Professional laptop bag fits up to 15.6 inch laptops. Multiple compartments for accessories. Water-resistant material. Light wear on straps.',
      category: 'electronics',
      location: 'Manhattan, NY',
      condition: 'good',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDfuxtnzY1VIVlzypbYeybkdonTorQ52kMDozXKfzyw2F7KHQlwbxEGpAYdceUETtxecuElwUhlK03zThcPZs1fdljfp0SjkghTDIkjaIf9KD2hfdzL4_IaA5N0haEn3K_m-9tTSiRXeR5u6VV-MeLM1Lv-Sb1moIvQ6_hoxZPl22-PaTL8vmIXrRrnZtGLBosAHQaavqJGpQE8LHHWlQtRAf4fHmO4PqcqaB_-SZjMOYDljp75ut8AXh-rgqD_yMnJbu1HQEKy0e0',
      user_id: demoUser.id
    },
    {
      title: 'Kids Bicycle',
      description: 'Colorful kids bicycle suitable for ages 5-8. Training wheels included. Recently serviced with new brake pads. A few paint chips but rides perfectly.',
      category: 'sports',
      location: 'Queens, NY',
      condition: 'good',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAWKxodB9_Ttksj4P8P6Ax4K7dpvVHMww34KXRa34diJ4vVFqGpryXN7VkW-TeqTBDjotXH6XYCGwgK0m8fRokbg9IuVS005_MgzC_qq4C4e6BiOtMnsF12ikD59L0h1602H3akyVb8fLQokB1kwyFc2w1CFLINDEd6KXcGhKa3J4uvI5fGTpTWNc6pCQXK5Uf7aghJ9F6Vc7gFtf_yudqcMA1PdvXBLp-TltWIYyWCQyD_zDR2u8cmHtxybxZrTkxI7L7eQumtr7o',
      user_id: regularUser.id
    },
    {
      title: 'Office Chair',
      description: 'Ergonomic office chair with adjustable height and lumbar support. Mesh back for breathability. Armrests slightly worn but the chair is very comfortable.',
      category: 'furniture',
      location: 'Brooklyn, NY',
      condition: 'like_new',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuADd3iA_cshxRvHCczAdgFlHhEXkWpBxlUo9rs73NMbJKYt1HxfxvQggMgIJ1F759T2gke_VUHWg_of8eYsSCgbvunLp9AisEHakaWdcK5uTpp7ATc4tiCMW-aEd2QVFW-FI13mJ3SGs_74-WQj9gV22iyOxh7qykSzE0A2yFHYha87BAtKLJwEe75i5LK9w58VmWuSj58SaovFbsxXKy8g93gBIWFlcQ9Ni8XixtY4W_cXvkBGEp03urcNo2cDdom1BXc2E0ywImM',
      user_id: demoUser.id
    },
    {
      title: 'Books Bundle',
      description: 'Collection of 8 classic literature hardcovers including works by Hemingway, Fitzgerald, and Orwell. All in excellent condition with minimal shelf wear.',
      category: 'books',
      location: 'Manhattan, NY',
      condition: 'like_new',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCs8SrQ3sFHsDXuNh5CQ3O_xEI6pPE2ri5DK5E8lKSFOUMHlOLbyX1NqZpMEzHG0RTXHrKj8oKKbthtnUhYJA39pr4H9DYbR5LrIG3mSHZ6SrVWNjSgS3RSwDeZZUCZ-rcLLkxUSf2rZjZDdkC53XRvnuI1xwFF7YeDwapSl1rbeKFZJcISOk5gtkBqdJMd8u7KrlzGR6vkfwpvkDaTzfJEAO0YQtAqwOetq_30X9YGVaXX84JpCa43ynhi9Yy1-DQtrzpLe7BqM0w',
      user_id: regularUser.id
    },
    {
      title: 'Vintage 35mm Camera',
      description: 'Classic film camera in working condition. Great for photography enthusiasts who appreciate analog photography. Comes with a leather case and 50mm lens.',
      category: 'electronics',
      location: 'Brooklyn, NY',
      condition: 'good',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCWdcLzECrr77BvVfL2tSUBE6u9-VaYIlFpUkLZIXqvWBzXQu8mUd5zBrg9sCFi3Xkp0wkj9c4Lz0f3azJfrO6kKNldRHHtVw0eYTxK7xTRPDiel8c_PovprLyyqtzwWaQbOLtZj7CeE-WQmZHjSgDkHXKwCyekzrrCGGXxqHjylqqOvnOtRTto5k6glfPerij-w1kKvRkodkGhK70_B035mvqwzBE8aRcI2ErhAczzkcpyI966ubBd_6HhZW-k1ItRuJ2EtkB4M8I',
      user_id: demoUser.id
    },
    {
      title: 'Modern Oak Desk',
      description: 'Solid oak workspace with built-in cable management. Minimalist Scandinavian design. Perfect for remote workers looking for a premium workspace upgrade.',
      category: 'furniture',
      location: 'Manhattan, NY',
      condition: 'like_new',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD4f83yGKrv7c7-0bfgp2ycKE2srMd0FytN5dTaA3XqR804zib_M9ZtNuYzfA3V4LuYthdImJII5_jXet_280-PzL-HkbcxzlwiwEXRG4iVuPaskmlExwya1v2EhIe4Yy8xyMt7R4C3E3ZC8d4rUmP26qZ2JSA73Q-fzRs_5UjxqmtMyeXdLPsJHuGI0ukjNtQZMKC1QnjdeAxSFvbEJo2ZYFUrGUxfE8yePRA6Z78ohXVupVg2YuZWnV_J1TydDhQ317LEL7Hwej0',
      user_id: regularUser.id
    },
    {
      title: 'Kitchen Mixer Set',
      description: 'Professional-grade kitchen stand mixer with multiple attachments. Includes dough hook, whisk, and paddle. Barely used — great for home bakers.',
      category: 'home',
      location: 'Queens, NY',
      condition: 'like_new',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC_D5HYJnYmY9drDnAxERAsykBT9zYUPZ4HGnRcbPhhQGdhp651CR_L0hOZwWtL6L3_8Z9jgyY8EoG42xKXXzWY6dAOgFKXefqB3-wmJKZTZ8QW0nyG4qUgHOJ3co7-sQaLyqgnvtarEST4ipxVqG4Kixyoaz8D6chy48XjwcAe_zvrwVfDkCdDkErZGpMRIO4kwMN9HunQAdE2XVxA-4HlvjmLHGTuIQXeDrfW-mpF5eGXuUTmdUhSvY_CjkcJd5QzRGB5YS7DzLc',
      user_id: demoUser.id
    },
    {
      title: 'Yoga Mat & Blocks',
      description: 'Premium non-slip yoga mat with two cork yoga blocks. Eco-friendly materials. Lightly used — perfect for beginners or experienced practitioners.',
      category: 'sports',
      location: 'Brooklyn, NY',
      condition: 'good',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBlMvBMHqQjbTmEyjWjXPd3wCSZy9yxEEIibqK_FrYZIKQEdayZyWZjDs1pyTV7BMcZ4WwfT24UmogIuCQlU4Qtoyh0t2Siqsrjd57TUPAErD6lg8C4_tyU7Rg5SA5J5fKBxkw_5q_7BmNSl-1tIfGJXtchuNNCD-R7OVp3vCJ6ZlkqBrPzsIOlKQ0eng-Xf76P79jpNIeDARUZin7fRZ6C4ratDUbhXjKn0E3LqsXlm5j97N6XeycB0PsgsM5l4aCqCIoYu-UoCmw',
      user_id: regularUser.id
    },
    {
      title: 'Garden Tool Set',
      description: 'Complete 12-piece garden tool set with ergonomic handles. Includes pruner, trowel, rake, and more. Stored in a convenient carry bag. Some rust on the pruner.',
      category: 'home',
      location: 'Queens, NY',
      condition: 'fair',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuFbHgTDNZc8mef5B_-mgrc5A2VXreSWFszi90qJ929cld8lxT2Zq2rr_xoFNuViq_07cM9XHh5brxdZteHkjkbnpGaoIlDlo2CpD9YCL22ErarSPQVkHT34xBCz7aR55Qt9CpLgCtO5I8oSacehKIbRtXSRRSUN4_tZzM7puQgsLp-HN36COjWnZaP865izbPC8Fd2JH3UbFhRj_CfZfbFsJJSgRZo-OY3BIDJ9_KwOdp5YGE1Z-hxpsipSWBMYKyYhY57fSF9jY',
      user_id: demoUser.id
    },
    {
      title: 'Wireless Headphones',
      description: 'Noise-cancelling Bluetooth headphones with 30-hour battery life. Comfortable over-ear design. Includes charging cable and carrying pouch.',
      category: 'electronics',
      location: 'Manhattan, NY',
      condition: 'like_new',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBjWm9dYJ4pLcPrsNeBN6UGPDQwvg-3ow2zE0sPTCIjzf2NiGvFqzUWvWVYAkK_9Jz3J0woqVG4xgwgT1bixXDTOwJ4zv373hpY0m2QZD7yWKJSQHpTcCw4-A2RuoTGK-WLNOxzxeDNc1L6jSS2p5yYpOqSjT3Vo9Fm4ThSKzz4WJW2ba_FV8PyvuAnM6IGLtz7Olw4vK2PJWk7GpeIDzQnnb4-t_UvbXCD_I2Q8Ie3xiXQlb_QS-mXqDbfbjUXzodT8cj_daW6Jqc',
      user_id: regularUser.id
    },
    {
      title: 'Minimalist Desk Lamp',
      description: 'A sleek, architectural LED desk lamp designed for modern productivity. Features dimmable light, swivel head, and USB charging port. Matte black finish.',
      category: 'home',
      location: 'Brooklyn, NY',
      condition: 'new',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCOAuimiFkOtfoe-ys0pZkiY12ssLWrTezacxKE7lqC1WHZPLmizYcGadcIVDHB6Cad2JiDKzzGJsHgyXZM_zD2fzBbXcvjY0OVXWYauCZKVnJ0duAeBFQgXHeUZM1d6zfqjPajccDvuqQP5tIqIKhv4tBIngJh2hroSWzXxeg_4ILmxxgeUzQRJsWIXnAwtGy_Jb0Di0RQCp5U0BOYUlCSeTAoCy7I-yFvxDKzR8x3oA9beXhJKMB4mceYtQyBzA14AOzJfYg-iAo',
      user_id: demoUser.id
    }
  ];

  await Item.bulkCreate(items);
  console.log(`✅ Seeded ${items.length} items and 3 users`);
  console.log('   👤 Admin: admin@revalue.com / admin123');
  console.log('   👤 User:  sarah@revalue.com / user123');
  console.log('   👤 User:  david@revalue.com / user123');
}

module.exports = seed;
